// Actual unmodified CF7 distribution in jsdom. All HTTP responses are synthetic.
const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const {JSDOM,VirtualConsole}=require('jsdom');
if(!process.env.CF7_ASSET_DIR)throw new Error('Set CF7_ASSET_DIR to an extracted official contact-form-7 package, not a live site');
const vendor=fs.readFileSync(process.env.CF7_ASSET_DIR+'/includes/js/index.js','utf8');
const extension=fs.readFileSync(__dirname+'/../assets/validation.js','utf8');
const markup='<div class="wpcf7 no-js" data-wpcf7-id="41"><div class="screen-reader-response"><p role="status"></p><ul></ul></div>'+
  '<form class="wpcf7-form init" data-status="init" novalidate>'+
  '<input type="hidden" name="_wpcf7" value="41"><input type="hidden" name="_wpcf7_version" value="fixture">'+
  '<input type="hidden" name="_wpcf7_locale" value="en_US"><input type="hidden" name="_wpcf7_unit_tag" value="wpcf7-f41-o1">'+
  '<input type="hidden" name="_wpcf7_container_post" value="0"><input type="hidden" name="_wpcf7_posted_data_hash">'+
  '<span class="wpcf7-form-control-wrap" data-name="your-name"><input class="wpcf7-form-control wpcf7-validates-as-required min-2" name="your-name" value="A" aria-invalid="false"></span>'+
  '<button type="submit" class="wpcf7-submit" name="send" value="send">Send</button><div class="wpcf7-response-output"></div></form></div>';
for(const order of ['before','after'])test('real CF7 retains submission/server validation with extension loaded '+order+' vendor initialization',async()=>{
  const errors=[],calls=[];
  const vc=new VirtualConsole();vc.on('jsdomError',e=>errors.push(e.message));vc.on('error',e=>errors.push(String(e)));
  const dom=new JSDOM('<!doctype html><body>'+markup,{url:'https://example.invalid',runScripts:'outside-only',virtualConsole:vc,pretendToBeVisual:true});
  const w=dom.window,d=w.document;
  const pause=()=>new Promise(resolve=>w.setTimeout(resolve,10));
  try{
    await pause(); // Let jsdom's automatic lifecycle finish before explicit fixture initialization.
    w.HTMLElement.prototype.getClientRects=()=>[{top:0,left:0,width:100,height:20}];
    w.HTMLElement.prototype.scrollIntoView=function(){};
    w.wp={i18n:{__:s=>s,_x:s=>s,sprintf:(s,...args)=>args.reduce((v,a)=>v.replace(/%[sd]/,a),s)}};
    w.wpcf7={api:{root:'https://example.invalid/wp-json/',namespace:'contact-form-7/v1'}};
    w.gcm_validation_settings={methods:[],min_prefix:'min-',max_prefix:'max-'};
    let rejectRequired=false;
    w.fetch=async(url,options)=>{
      calls.push({url,options});
      const data=options.method==='GET'?{rules:[]}:(rejectRequired?{
        status:'validation_failed',message:'Review required fields.',invalid_fields:[{field:'your-name',message:'Required field.',error_id:'fixture-required'}],
      }:{status:'mail_failed',message:'Synthetic mail transport is disabled.',invalid_fields:[]});
      return {status:200,json:async()=>data};
    };
    if(order==='before')w.eval(extension);
    w.eval(vendor);d.dispatchEvent(new w.Event('DOMContentLoaded'));
    await pause();
    const submitApi=w.wpcf7.submit;
    if(order==='after')w.eval(extension);
    assert.equal(typeof submitApi,'function');assert.equal(w.wpcf7.submit,submitApi);
    const form=d.querySelector('form'),field=d.querySelector('[name="your-name"]'),button=d.querySelector('button');
    const submit=()=>form.dispatchEvent(new w.SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:button}));
    submit();await pause();
    assert.equal(calls.filter(c=>c.options.method==='POST').length,0,'Optional hint stops before a native AJAX call');
    assert.match(d.querySelector('.gcm-validation-tip').textContent,/Minimum length/);
    field.value='Fixture';field.dispatchEvent(new w.Event('input',{bubbles:true}));submit();await pause();
    const posts=calls.filter(c=>c.options.method==='POST');
    assert.equal(posts.length,1);assert.equal(posts[0].options.body.get('your-name'),'Fixture');assert.equal(posts[0].options.body.get('send'),'send');
    assert.match(d.querySelector('.wpcf7-response-output').innerText,/Synthetic mail transport is disabled/);
    // The optional hint layer does not replace authoritative server validation.
    rejectRequired=true;field.value='';submit();await pause();
    assert.equal(calls.filter(c=>c.options.method==='POST').length,2);
    assert.equal(field.getAttribute('aria-invalid'),'true');
    assert.match(d.querySelector('.wpcf7-not-valid-tip').textContent,/Required field/);
    assert.equal(w.wpcf7.submit,submitApi);assert.deepEqual(errors,[]);
  }finally{dom.window.close();}
});
