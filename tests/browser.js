async page => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const id = 'f73eae6b-6c36-49f6-9b66-8cbdaf51792b';
  const address = (postcode = '2450', title = 'Sluseholmen 1A, 2450 København SV') => ({
    status: '3', id_lokalid: id, adressebetegnelse: title,
    husnummer: {vejnavn: 'Sluseholmen', husnummertekst: '1A', postnummer: {postnr: postcode}}
  });
  let result = address(), searchRows = [{type:'adresse', id, titel:result.adressebetegnelse}], mode='ok';
  let requests = 0;
  // No external traffic, booking submission, or Stripe call can escape the fixture.
  await page.route('**/*', async route => {
    if (!route.request().url().startsWith('https://adressevaelger.dk/')) return route.abort();
    requests++;
    if (mode === 'error') return route.fulfill({status:503,body:'Unavailable',headers:{'access-control-allow-origin':'*'}});
    const path = new URL(route.request().url()).pathname;
    const body = path.endsWith('/soeg') ? {status:'ok',fund:searchRows} :
      path.startsWith('/husnumre/') ? {status:'ok',adresser:[result, {...result,adressebetegnelse:'Sluseholmen 1, 1. th., 2450 København SV'}]} : {status:'ok',adresse:result};
    await route.fulfill({json:body,headers:{'access-control-allow-origin':'*'}});
  });
  await page.goto('about:blank');
  await page.setContent(`<form class="idealrent_checkout_form fullpage_layout">
    <div class="idealrent_checkout_form_progress"><div><button type="button" data-section="4"><span class="value"></span></button></div></div>
    <div class="idealrent_checkout_form_block active" id="address-block"><div class="idealrent_checkout_form_section section_4">
      <div class="idealrent_checkout_form_element"><input id="idealrent_checkout_address" placeholder="Angiv din fulde adresse"><input type="hidden" id="idealrent_checkout_address_complete"></div>
      <div class="idealrent_checkout_form_element"><label><input type="radio" name="idealrent_checkout_entrance" value="home">Hjemme</label></div>
      <button type="button" class="continue" data-section="4">Fortsæt</button>
    </div></div><div class="idealrent_checkout_form_block" id="next-block">Next step</div><div id="card-element"></div></form>`);
  await page.addScriptTag({path:'/tmp/idealrent-test-jquery.js'});
  await page.addScriptTag({content:`window.IR_Checkout_settings={stripekey:'test'};window.Stripe=function(){return {elements:function(){return {create:function(){return {mount:function(){}}}}},createToken:function(){throw Error('Payment forbidden')}}};document.addEventListener('submit',function(e){e.preventDefault();throw Error('Booking forbidden')});`});
  for (const file of ['assets/jquery-ui.js','assets/dawa-autocomplete.js','assets/idealrent_checkout.js']) await page.addScriptTag({path:file});
  for (const file of ['assets/jquery-ui.css','assets/idealrent_checkout.css']) await page.addStyleTag({path:file});
  await page.waitForFunction(() => !!jQuery('#idealrent_checkout_address').data('dawa-dawaautocomplete'));
  const input = page.locator('#idealrent_checkout_address');
  const hidden = page.locator('#idealrent_checkout_address_complete');
  const menu = page.locator('.ui-autocomplete');
  const assert = (condition, message) => {if(!condition) throw Error(message);};
  const search = async text => {await input.fill(text);await menu.waitFor({state:'visible'});};
  const choose = async () => {await input.press('ArrowDown');await input.press('ArrowUp');await input.press('Enter');await page.waitForFunction(() => !!jQuery('#idealrent_checkout_address_complete').val());};
  await search('Sluseholmen');
  await choose();
  assert(await hidden.inputValue() === result.adressebetegnelse, 'keyboard selection mapping');
  assert(await page.locator('.continue').evaluate(el=>el.classList.contains('active')), 'address alone enables continuation in live version 1.2');
  await page.locator('.continue').dispatchEvent('click');
  assert(await page.locator('#next-block').evaluate(el=>el.classList.contains('active')), 'progression');
  await page.evaluate(()=>{jQuery('#address-block').addClass('active');jQuery('#next-block').removeClass('active');});
  await input.fill('Changed');
  assert(await hidden.inputValue() === '', 'editing clears hidden selection');
  await page.locator('.continue').dispatchEvent('click');
  assert(!await page.locator('#next-block').evaluate(el=>el.classList.contains('active')), 'unresolved address blocked');
  await input.fill('');
  await page.waitForTimeout(250);
  const before = requests;
  await input.fill(' ');await page.waitForTimeout(250);
  assert(requests === before, 'blank input request suppression');
  searchRows = [{type:'husnummer',id,titel:'Sluseholmen 1, 2450 København SV'}];
  await search('Sluseholmen 1');
  await menu.locator('li').first().click();
  await page.waitForFunction(()=>jQuery('.ui-autocomplete:visible li').length===2);
  await menu.locator('li').nth(1).click();
  assert((await hidden.inputValue()).includes('1. th.'), 'apartment selection');
  result = address('8000','Testvej 1, 8000 Aarhus C');
  searchRows = [{type:'adresse',id,titel:result.adressebetegnelse}];
  await search('Testvej');await menu.locator('li').first().click();
  await page.locator('.idealrent_checkout_zip_error').waitFor();
  assert(await hidden.inputValue()==='', 'out of area clears selection');
  await page.locator('.idealrent_checkout_zip_error button').click();
  result = address('2450','Æblevej <img src=x onerror=alert(1)>, 2450 København SV');
  searchRows = [{type:'adresse',id,titel:result.adressebetegnelse}];
  await search('Æblevej');await menu.locator('li').first().click();
  await page.waitForFunction(()=>!!jQuery('#idealrent_checkout_address_complete').val());
  assert(await page.locator('.idealrent_checkout_form_progress img').count()===0,'summary renders text');
  assert(await page.locator('.ui-autocomplete img').count()===0,'suggestions render text');
  mode='error';await input.fill('Failure');
  await page.waitForFunction(()=>jQuery('[role=status]').text().includes('Prøv igen'));
  assert(await hidden.inputValue()==='', 'failure cannot retain old selection');
  mode='ok';searchRows=[];await input.fill('No results');
  await page.waitForFunction(()=>jQuery('[role=status]').text().includes('Ingen adresser'));
  // Force late completions even after abort to exercise the browser's input handler.
  await page.evaluate(() => {
    const $ = jQuery, input = $('#idealrent_checkout_address'), original = $.ajax, pending = [];
    $.ajax = () => { const d = $.Deferred(); const xhr = d.promise(); xhr.abort = () => {}; pending.push(d); return xhr; };
    try {
      input.val('Old').trigger('input').autocomplete('search');
      input.val('New').trigger('input').autocomplete('search');
      pending[1].resolve({status:'ok',fund:[{type:'vejnavn',titel:'New road',vejnavn:'New road'}]});
      pending[0].resolve({status:'ok',fund:[{type:'vejnavn',titel:'Old road',vejnavn:'Old road'}]});
      if (!$('.ui-autocomplete:visible').text().includes('New road') || $('.ui-autocomplete:visible').text().includes('Old road')) throw Error('Stale suggestions');
      input.dawaautocomplete('instance')._select({type:'adresse',id:'f73eae6b-6c36-49f6-9b66-8cbdaf51792b',titel:'Old address'});
      input.val('').trigger('input');
      pending[2].resolve({status:'ok',adresse:{status:'3',id_lokalid:'f73eae6b-6c36-49f6-9b66-8cbdaf51792b',adressebetegnelse:'Old address',husnummer:{vejnavn:'Old',husnummertekst:'1',postnummer:{postnr:'2450'}}}});
      if (input.val() || $('#idealrent_checkout_address_complete').val()) throw Error('Stale selection');
      if (input.autocomplete('instance').pending !== 0) throw Error('Unsettled response');
    } finally { $.ajax = original; }
  });
  await page.evaluate(()=>jQuery('#idealrent_checkout_address').dawaautocomplete('destroy'));
  assert(await page.locator('[role=status]').count()===0,'destroy removes status');
  assert(errors.length===0, 'page errors: '+errors.join('; '));
  return {passed: ['keyboard selection','postcode mapping','address progression without entrance','editing invalidation','unresolved continuation guard','blank requests','building expansion','mouse apartment selection','service area rejection','Danish characters','safe rendering','API failure','empty results','destroy cleanup','out-of-order suggestions','clearing during selection'], requests, errors};
}
