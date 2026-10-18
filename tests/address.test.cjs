const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const id = 'f73eae6b-6c36-49f6-9b66-8cbdaf51792b';
const record = {status:'3', id_lokalid:id, adressebetegnelse:'Sluseholmen 1A, 2450 København SV',
  husnummer:{vejnavn:'Sluseholmen', husnummertekst:'1A', postnummer:{postnr:'2450'}}};
function setup() {
  let definition, value = '', requests = [], events = [], responses = [];
  const $ = {widget: (name, def) => definition=def, extend:Object.assign,
    ajax: options => { const xhr={options, done(fn){this.success=fn;return this;},
      fail(fn){this.failure=fn;return this;}, abort(){this.aborted=true;this.failure({},'abort');}};
      requests.push(xhr);return xhr;}};
  vm.runInNewContext(fs.readFileSync('assets/dawa-autocomplete.js','utf8'), {jQuery:$});
  const widget=Object.assign({}, definition, {generation:0,
    element:{0:{setSelectionRange(){}}, val(v){if(v!==undefined)value=v;return value;}, autocomplete(){}},
    status:{text(v){widget.message=v;}}, _trigger:(name,event,data)=>events.push({name,data}),
    _delay:fn=>fn()});
  return {widget,requests,events,responses,search(q='Sluseholmen'){widget._search(q,r=>responses.push(r));},
    get value(){return value;}};
}
test('search contract and all supported suggestion types',()=>{
 const s=setup();s.search();const req=s.requests[0];
 assert.equal(req.options.url,'https://adressevaelger.dk/adresser/soeg');
 assert.equal(req.options.data.tekst,'Sluseholmen');assert.equal(req.options.data.maksimum,20);
 req.success({status:'ok',fund:['vejnavn','navngivenvejpostnummer','vejnavnhusnummer','husnummer','adresse'].map(type=>({type,id,titel:'Test'}))});
 assert.equal(s.responses[0].length,5);
});
test('selection maps authoritative postcode and metadata',()=>{
 const s=setup();s.widget._select({type:'adresse',id,titel:'Misleading 9999'});
 s.requests[0].success({status:'ok',adresse:{...record,etagebetegnelse:'st',doerbetegnelse:'th'}});
 const result=s.events.find(e=>e.name==='select').data;
 assert.equal(result.data.postnr,'2450');assert.equal(result.data['dør'],'th');assert.equal(result.data.etage,'st');
 assert.equal(result.tekst,record.adressebetegnelse);
});
test('invalid and inactive records never complete selection',()=>{
 for(const changes of [{husnummer:null},{status:'4'},{id_lokalid:'bad'},{adressebetegnelse:''}]) {
 const s=setup();s.widget._select({type:'adresse',id,titel:'Test'});
 s.requests[0].success({status:'ok',adresse:{...record,...changes}});
 assert.ok(!s.events.some(e=>e.name==='select'));assert.match(s.widget.message,/Prøv igen/);
 }
});
test('building lookup resolves one address or offers multiple',()=>{
 for(const count of [1,2]){const s=setup();s.widget._select({type:'husnummer',id,titel:'Test'});
 assert.ok(s.requests[0].options.url.endsWith('/'+id+'/adresser'));
 s.requests[0].success({status:'ok',adresser:Array(count).fill(record)});
 assert.equal(s.events.filter(e=>e.name==='select').length,count===1?1:0);
 if(count===2){s.search();assert.equal(s.responses[0].length,2);}
 }
});
test('street refinement preserves postcode and handles provider field casing',()=>{
 const s=setup();s.widget._select({type:'navngivenvejpostnummer',titel:'Test',vejnavn:'Æblevej',postnr:'2450',postdistrikt:'København SV'});
 s.search();assert.equal(s.requests[0].options.data.postnummer,'2450');assert.equal(s.requests[0].options.data.vejnavn,'Æblevej');
 const b=setup();b.widget._select({type:'vejnavnhusnummer',titel:'Test',vejNavn:'Æblevej',husNummer:'2'});b.search();
 assert.equal(b.requests[0].options.data.husnummer,'2');
});
test('empty, short and excessive inputs make no requests',()=>{
 const s=setup();for(const q of ['', ' ', 'S', 'a'.repeat(74)])s.search(q);
 assert.equal(s.requests.length,0);assert.equal(s.responses.length,4);
});
test('malformed, empty and error responses settle the source',()=>{
 for(const payload of [null,{}, {status:'error',fund:[]},{status:'ok',fund:[]},{status:'ok',fund:[null,{}]}]){
 const s=setup();s.search();s.requests[0].success(payload);assert.equal(s.responses[0].length,0);assert.equal(s.widget.pendingResponse,null);
 }
 for(const reason of ['timeout','error','parsererror']) {const s=setup();s.search();s.requests[0].failure({},reason);
 assert.equal(s.responses[0].length,0);assert.match(s.widget.message,/Prøv igen/);}
});
test('superseded searches settle once and ignore late success even after abort',()=>{
 const s=setup();s.search('Old');s.search('New');assert.equal(s.requests[0].aborted,true);
 s.requests[0].success({status:'ok',fund:[{type:'adresse',id,titel:'Old'}]});
 s.requests[1].success({status:'ok',fund:[{type:'adresse',id,titel:'New'}]});
 assert.equal(s.responses.length,2);assert.equal(s.responses[0].length,0);assert.equal(s.responses[1][0].label,'New');
});
test('editing or clearing during selection prevents stale address completion',()=>{
 const s=setup();s.widget._select({type:'adresse',id,titel:'Old'});s.widget._invalidate();
 s.requests[0].success({status:'ok',adresse:record});assert.ok(!s.events.some(e=>e.name==='select'));
});
