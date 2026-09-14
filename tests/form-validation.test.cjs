const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const {execFileSync}=require('node:child_process');
const {JSDOM,VirtualConsole}=require('jsdom');
const root=require('node:path').resolve(__dirname,'..');
const source=process.env.GCM_VALIDATION_REVISION
  ?execFileSync('git',['show',process.env.GCM_VALIDATION_REVISION+':assets/validation.js'],{cwd:root,encoding:'utf8'})
  :fs.readFileSync(root+'/assets/validation.js','utf8');
const settings={methods:[{class:'letters',methods:['letters','spaces']}],min_prefix:'min-',max_prefix:'max-'};
async function fixture(markup,{config=settings,api}={}) {
  const errors=[];
  const vc=new VirtualConsole();vc.on('jsdomError',e=>errors.push(e.message));
  const dom=new JSDOM('<!doctype html><body>'+markup,{url:'https://example.invalid',runScripts:'outside-only',virtualConsole:vc,pretendToBeVisual:true});
  const w=dom.window,d=w.document;
  // jsdom does not lay out elements; visibility is explicit synthetic fixture state.
  w.HTMLElement.prototype.getClientRects=function(){return this.closest('[hidden]')?[]:[{top:0,left:0,width:100,height:20}];};
  Object.defineProperty(w.HTMLElement.prototype,'offsetWidth',{get(){return this.closest('[hidden]')?0:100;}});
  w.HTMLElement.prototype.scrollIntoView=function(){};
  if(process.env.GCM_JQUERY_SOURCE)w.eval(fs.readFileSync(process.env.GCM_JQUERY_SOURCE,'utf8'));
  if(api)w.wpcf7=api;
  w.gcm_validation_settings=config;
  await new Promise(resolve=>w.setTimeout(resolve,0));
  try{w.eval(source);}catch(error){errors.push(error.message);}
  await new Promise(resolve=>w.setTimeout(resolve,20));
  return {w,d,errors,close:()=>dom.window.close(),submit(form=d.querySelector('form')){
    const event=new w.SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:form.querySelector('button')});
    form.dispatchEvent(event);return event;
  }};
}
const form=content=>'<form class="wpcf7-form" novalidate>'+content+'<button type="submit">Send</button></form>';
test('absent CF7 does not throw or fabricate a submit API',async()=>{
  const f=await fixture(form('<input name="name">'));try{assert.deepEqual(f.errors,[]);assert.equal(f.w.wpcf7,undefined);}finally{f.close();}
});
test('existing vendor submit API is never replaced',async()=>{
  const submit=()=>{};const f=await fixture(form('<input name="name">'),{api:{submit}});
  try{assert.equal(f.w.wpcf7.submit,submit);assert.deepEqual(f.errors,[]);}finally{f.close();}
});
test('unrelated forms are not intercepted even when class names overlap',async()=>{
  const f=await fixture('<form><input class="max-2" value="unrelated"><button>Search</button></form>');
  try{const field=f.d.querySelector('input');field.dispatchEvent(new f.w.Event('change',{bubbles:true}));
    assert.equal(f.submit().defaultPrevented,false);assert.equal(f.d.querySelector('.gcm-validation-tip'),null);assert.deepEqual(f.errors,[]);
  }finally{f.close();}
});
test('invalid configured hints stop before the native submit listener and focus the field',async()=>{
  const f=await fixture(form('<input class="min-4" value="ab">'));try{let calls=0;const el=f.d.querySelector('form');el.addEventListener('submit',()=>calls++);
    assert.equal(f.submit().defaultPrevented,true);assert.equal(calls,0);assert.equal(f.d.activeElement,f.d.querySelector('input'));
    assert.match(f.d.querySelector('.gcm-validation-tip').textContent,/Minimum length: 4/);assert.deepEqual(f.errors,[]);
  }finally{f.close();}
});
test('valid configured hints reach the vendor listener once with the original submitter',async()=>{
  const f=await fixture(form('<input class="letters min-2 max-20" value="Test Person">'));
  try{const seen=[];f.d.querySelector('form').addEventListener('submit',e=>seen.push(e.submitter));assert.equal(f.submit().defaultPrevented,false);
    assert.deepEqual(seen,[f.d.querySelector('button')]);assert.deepEqual(f.errors,[]);
  }finally{f.close();}
});
test('a field without CF7 wrapper gets safe text feedback and no exception',async()=>{
  const f=await fixture(form('<input class="letters" value="123">'));try{f.submit();const tip=f.d.querySelector('.gcm-validation-tip');
    assert.equal(tip.previousElementSibling,f.d.querySelector('input'));assert.equal(tip.getAttribute('role'),'alert');assert.equal(tip.children.length,0);assert.deepEqual(f.errors,[]);
  }finally{f.close();}
});
test('clearing our hint preserves vendor errors and existing accessible descriptions',async()=>{
  const f=await fixture(form('<span class="wpcf7-form-control-wrap"><input class="letters" value="123" aria-describedby="help server" aria-invalid="true"><span id="server" class="wpcf7-not-valid-tip">Server validation</span></span><span id="help">Help</span>'));
  try{const field=f.d.querySelector('input');f.submit();const id=f.d.querySelector('.gcm-validation-tip').id;
    assert.ok(field.getAttribute('aria-describedby').split(' ').includes(id));assert.ok(f.d.getElementById('server'));
    field.value='Valid';field.dispatchEvent(new f.w.Event('input',{bubbles:true}));assert.equal(f.d.querySelector('.gcm-validation-tip'),null);
    assert.equal(field.getAttribute('aria-describedby'),'help server');assert.equal(field.getAttribute('aria-invalid'),'true');assert.ok(f.d.getElementById('server'));
  }finally{f.close();}
});
test('hidden, disabled and readonly fields cannot block a form via optional hints',async()=>{
  const f=await fixture(form('<span hidden><input class="letters" value="123"></span><input disabled class="letters" value="123"><input readonly class="letters" value="123">'));
  try{assert.equal(f.submit().defaultPrevented,false);assert.deepEqual(f.errors,[]);}finally{f.close();}
});
test('required, checkbox and radio rules remain owned by native/vendor validation',async()=>{
  const f=await fixture(form('<input required class="letters min-4"><input type="radio" required name="choice"><input type="checkbox" required>'));
  try{let calls=0;f.d.querySelector('form').addEventListener('submit',()=>calls++);assert.equal(f.submit().defaultPrevented,false);assert.equal(calls,1);}finally{f.close();}
});
test('malformed optional configuration and unsupported patterns do not crash or invent regex',async()=>{
  const f=await fixture(form('<input class="letters false4 min-NaN" value="123">'),{config:{methods:[null,{class:'letters',methods:['RegEx-3190']}],min_prefix:false,max_prefix:{}}});
  try{assert.equal(f.submit().defaultPrevented,false);assert.deepEqual(f.errors,[]);}finally{f.close();}
});
test('explicit opt-in and dynamically inserted forms work without duplicate listeners',async()=>{
  const f=await fixture('');try{f.w.eval(source);f.d.body.innerHTML='<form data-gcm-validation><input class="max-2" value="three"><button>Send</button></form>';
    assert.equal(f.submit().defaultPrevented,true);assert.equal(f.d.querySelectorAll('.gcm-validation-tip').length,1);
    f.d.querySelector('input').value='ok';let calls=0;f.d.querySelector('form').addEventListener('submit',()=>calls++);
    assert.equal(f.submit().defaultPrevented,false);assert.equal(calls,1);
  }finally{f.close();}
});
test('form reset removes only owned hint and description',async()=>{
  const f=await fixture(form('<input class="letters" value="123" aria-describedby="help"><span id="help">Help</span>'));
  try{f.submit();f.d.querySelector('form').reset();assert.equal(f.d.querySelector('.gcm-validation-tip'),null);assert.equal(f.d.querySelector('input').getAttribute('aria-describedby'),'help');}finally{f.close();}
});
