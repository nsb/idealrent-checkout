(function($) {
  'use strict';

  function text(value) { return typeof value === 'string' && value.trim().length > 0; }
  function validId(value) { return typeof value === 'string' && /^[0-9a-f-]{36}$/i.test(value); }

  // Keep the booking callback's DAWA shape; postcode comes from DAR, never the label.
  function address(value) {
    var house = value && value.husnummer;
    var postcode = house && house.postnummer && house.postnummer.postnr;
    if (!value || value.status !== '3' || !validId(value.id_lokalid) ||
        !text(value.adressebetegnelse) || !/^\d{4}$/.test(postcode || '') ||
        !house || !text(house.vejnavn) || !text(house.husnummertekst)) return null;
    return {
      type: 'adresse', tekst: value.adressebetegnelse,
      forslagstekst: value.adressebetegnelse, caretpos: value.adressebetegnelse.length,
      data: {id: value.id_lokalid, postnr: String(postcode), vejnavn: house.vejnavn,
        husnr: house.husnummertekst, etage: value.etagebetegnelse || '',
        'dør': value.doerbetegnelse || ''}
    };
  }

  function suggestions(payload) {
    if (!payload || payload.status !== 'ok' || !Array.isArray(payload.fund)) return null;
    return payload.fund.filter(function(row) {
      return row && text(row.titel) &&
        (/^(vejnavn|navngivenvejpostnummer|vejnavnhusnummer)$/.test(row.type) ||
          (/^(adresse|husnummer)$/.test(row.type) && validId(row.id)));
    }).map(function(row) { return {label: row.titel, value: row}; });
  }

  $.widget('dawa.dawaautocomplete', {
    options: {
      baseUrl: 'https://adressevaelger.dk',
      // KDS's public placeholder token, not a private credential.
      token: 'adressevaelger123', minLength: 2, delay: 200,
      autoFocus: true, timeout: 10000, error: null
    },

    _create: function() {
      var self = this;
      this.generation = 0;
      this.status = $('<span role="status" aria-live="polite"></span>').insertAfter(this.element);
      this.element.autocomplete({
        minLength: this.options.minLength, delay: this.options.delay,
        autoFocus: this.options.autoFocus,
        source: function(request, response) { self._search(request.term, response); },
        focus: function(event) { event.preventDefault(); },
        select: function(event, ui) {
          event.preventDefault();
          self._select(ui.item.value);
        }
      });
      this._on(this.element, {input: function() {
        this._invalidate();
        this.children = null;
        this.refinement = null;
        this.element.autocomplete('close');
        this.status.text('');
        this._trigger('clear');
      }});
    },

    _invalidate: function() {
      this.generation++;
      if (this.pendingResponse) {
        var response = this.pendingResponse;
        this.pendingResponse = null;
        response([]);
      }
      if (this.xhr) {
        var xhr = this.xhr;
        this.xhr = null;
        xhr.abort();
      }
    },

    _fail: function() {
      this.element.autocomplete('close');
      this.status.text('Adressen kunne ikke hentes. Prøv igen.');
      this._trigger('error');
    },

    _get: function(path, params, done) {
      var self = this;
      var generation = this.generation;
      this.xhr = $.ajax({
        url: this.options.baseUrl + path,
        data: $.extend({}, params, {token: this.options.token}),
        dataType: 'json', timeout: this.options.timeout
      }).done(function(data) {
        if (generation === self.generation) done(data);
      }).fail(function(xhr, reason) {
        if (generation !== self.generation || reason === 'abort') return;
        if (self.pendingResponse) {
          var response = self.pendingResponse;
          self.pendingResponse = null;
          response([]);
        }
        self._fail();
      });
    },

    _search: function(term, response) {
      this._invalidate();
      this.status.text('');
      if (this.children) {
        var children = this.children;
        this.children = null;
        response(children);
        return;
      }
      term = term.trim();
      if (term.length < this.options.minLength || term.length > 73) {
        response([]);
        return;
      }
      var params = this.refinement || {tekst: term};
      this.refinement = null;
      params.maksimum = 20;
      this.pendingResponse = response;
      var self = this;
      this._get('/adresser/soeg', params, function(payload) {
        var rows = suggestions(payload);
        self.pendingResponse = null;
        response(rows || []);
        if (rows === null) self._fail();
        else if (!rows.length) self.status.text('Ingen adresser fundet.');
      });
    },

    _complete: function(value) {
      this.element.val(value.tekst);
      this.element[0].setSelectionRange(value.caretpos, value.caretpos);
      this.element.autocomplete('close');
      this.status.text('');
      this._trigger('select', null, value);
    },

    _select: function(row) {
      var self = this;
      this._invalidate();
      this._trigger('clear');
      this.status.text('');
      if (row && row.resolved) {
        this._complete(row.resolved);
        return;
      }
      if (!row || !text(row.titel)) { this._fail(); return; }
      this.element.val(row.titel);
      if (row.type === 'adresse' && validId(row.id)) {
        this._get('/adresser/' + row.id, {}, function(payload) {
          var value = payload && payload.status === 'ok' && address(payload.adresse);
          if (value) self._complete(value);
          else self._fail();
        });
      } else if (row.type === 'husnummer' && validId(row.id)) {
        this._get('/husnumre/' + row.id + '/adresser', {}, function(payload) {
          if (!payload || payload.status !== 'ok' || !Array.isArray(payload.adresser)) {
            self._fail(); return;
          }
          var rows = payload.adresser.map(address).filter(function(value) { return !!value; });
          if (rows.length === 1) self._complete(rows[0]);
          else if (rows.length) {
            self.children = rows.map(function(value) {
              return {label: value.tekst, value: {resolved: value}};
            });
            self.element.autocomplete('search');
          } else self._fail();
        });
      } else if (/^(vejnavn|navngivenvejpostnummer|vejnavnhusnummer)$/.test(row.type)) {
        var street = row.vejnavn || row.vejNavn;
        if (!text(street)) { this._fail(); return; }
        var house = row.husnummer || row.husNummer;
        var prefix = street + ' ' + (house ? house + ' ' : '');
        this.element.val(prefix + (row.postnr ? ', ' + row.postnr + ' ' + (row.postdistrikt || '') : ''));
        this.element[0].setSelectionRange(prefix.length, prefix.length);
        this.refinement = {vejnavn: street};
        if (house) this.refinement.husnummer = house;
        if (row.postnr) this.refinement.postnummer = row.postnr;
        // Let jQuery UI finish closing the selected menu before opening its refinement.
        var generation = this.generation;
        this._delay(function() {
          if (generation === self.generation) self.element.autocomplete('search');
        });
      } else this._fail();
    },

    _destroy: function() {
      this._invalidate();
      this.element.autocomplete('destroy');
      this.status.remove();
    }
  });
})(jQuery);
