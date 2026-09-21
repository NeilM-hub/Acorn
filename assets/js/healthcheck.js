import {request} from './api.js';
import {load,save,clear,saveLocalPendingMutation,flushPendingMutations} from './state.js';
import {app,escapeHtml,focusHeading,error} from './ui.js';

const root=document.querySelector('#acorn-healthcheck');

if(root){
 window.acornHealthcheck={rest:root.dataset.rest,reportBase:root.dataset.reportBase};

 let token=null,state=null,busy=false,currentStep=null,history=[];
 const labels={yes:'Yes',partly:'Partly',no:'No',not_sure:'Not sure'};
 const coreKeys=['M01_COMPETENT_PERSON','M02_POLICY','R01_GENERAL_RA','R02_ACTION_REVIEW','P01_TRAINING_INDUCTION','M04_CONSULTATION','A01_FIRST_AID','A04_INCIDENT_REPORTING','W03_WORKPLACE_EQUIPMENT'];
 const fireKeys=['F02_FIRE_RA','F06_FIRE_ARRANGEMENTS'];
 const legionellaKeys=['L04_LEGIONELLA_MANAGEMENT'];
 const asbestosKeys=['AS05_ASBESTOS_MANAGEMENT'];
 const specialistKeys=['S01_SELECTED_RISK_CONTROLS'];
 const finalKeys=['E01_EMPLOYERS_LIABILITY','E02_LAW_INFORMATION'];
 const riskLabels={
  dse:'Display screen equipment',
  manual_handling:'Manual handling',
  hazardous_substances:'Hazardous substances',
  lone_working:'Lone working',
  work_at_height:'Work at height',
  machinery:'Machinery / equipment',
  contractors:'Contractors',
  young_workers:'Young workers',
  driving_for_work:'Driving for work'
 };
 const stages=['Managing Safety','People & Workplace','Fire','Premises & Risks','Finish'];

 const stageRail=(active,label)=>`
   <div class="acorn-hc__stage-head">
     <span class="acorn-hc__eyebrow">${escapeHtml(label)}</span>
     <span class="acorn-hc__stage-count">Section ${active+1} of 5</span>
   </div>
   <div class="acorn-hc__stage-rail" aria-label="Healthcheck progress">
     ${stages.map((stage,i)=>`<span class="${i<active?'is-complete ':''}${i===active?'is-current':''}" title="${escapeHtml(stage)}"></span>`).join('')}
   </div>`;

 function landing(resume=false){
  currentStep={type:'landing'};
  app().innerHTML=`
   <div class="acorn-hc__hero">
    <span class="acorn-hc__eyebrow">Free Health &amp; Safety Healthcheck</span>
    <h1>Find the gaps.<br>Know what to do next.</h1>
    <p class="acorn-hc__hero-copy">Answer a few straightforward questions and get a practical snapshot of what looks good, what may need attention and what to do next.</p>
    <div class="acorn-hc__topic-chips" aria-label="Topics covered">
      <span>Health &amp; Safety</span><span>Fire Safety</span><span>Legionella</span><span>Asbestos</span>
    </div>
    <div class="acorn-hc__benefits">
      <span>✓ Around 3–4 minutes</span><span>✓ No account needed</span><span>✓ Personalised action plan</span><span>✓ Downloadable report</span>
    </div>
    <p class="acorn-hc__notice">This is an indicative self-assessment based on the information you provide. It is not a formal audit, legal advice or confirmation of compliance.</p>
    ${resume?'<div class="acorn-hc__actions"><button class="acorn-hc__primary" data-action="continue">Continue Healthcheck</button><button class="acorn-hc__secondary" data-action="restart">Start again</button></div>':'<button class="acorn-hc__primary acorn-hc__hero-cta" data-action="start">Start my Healthcheck</button>'}
   </div>`;
 }

 function tailoring(){
  currentStep={type:'tailoring'};
  const p=state?.profile||{};
  app().innerHTML=`
   ${stageRail(0,'Quick setup')}
   <div class="acorn-hc__question-wrap">
    <h2>A couple of details so we can tailor the Healthcheck</h2>
    <p class="acorn-hc__lead">We only use these answers to show the checks that are relevant to you.</p>
    <form data-form="tailoring">
      ${choiceGroup('jurisdiction','Where is your main workplace?',[['england','England'],['wales','Wales'],['scotland','Scotland'],['northern_ireland','Northern Ireland']],p.jurisdiction)}
      ${choiceGroup('employee_band','How many people do you employ?',[['none','None'],['1_4','1–4'],['5_9','5–9'],['10_49','10–49'],['50_249','50–249'],['250_plus','250+']],p.employee_band)}
      <p data-save aria-live="polite"></p>
      <button class="acorn-hc__primary" type="submit">Start the questions</button>
    </form>
   </div>`;
  focusHeading();
 }

 function choiceGroup(name,legend,options,selected=''){
  return `<fieldset class="acorn-hc__tile-group"><legend>${escapeHtml(legend)}</legend><div class="acorn-hc__tile-grid">${options.map(([value,text])=>`<label class="acorn-hc__choice"><input type="radio" name="${name}" value="${value}" required ${selected===value?'checked':''}><span>${escapeHtml(text)}</span></label>`).join('')}</div></fieldset>`;
 }

 function questionByKey(key){return (state.questions||[]).find(q=>q.key===key);}
 function firstUnanswered(keys){return keys.map(questionByKey).find(q=>q&&!state.answers[q.key])||null;}
 function hasProfile(key){return Object.prototype.hasOwnProperty.call(state.profile||{},key);}

 function stageForQuestion(q){
  if(coreKeys.slice(0,4).includes(q.key))return [0,'Managing Safety'];
  if(coreKeys.slice(4).includes(q.key))return [1,'People & Workplace'];
  if(fireKeys.includes(q.key))return [2,'Fire Safety'];
  if(legionellaKeys.includes(q.key)||asbestosKeys.includes(q.key)||specialistKeys.includes(q.key))return [3,q.module==='legionella'?'Legionella':q.module==='asbestos'?'Asbestos':'Your workplace risks'];
  return [4,'Final checks'];
 }

 function helpPanel(help){
  const normalized=String(help||'').replace(/\\n/g,'\n');
  const marker='\n\nWhat good looks like:';
  const parts=normalized.split(marker);
  const good=parts[1]||'';
  return `<button class="acorn-hc__help" type="button" aria-expanded="false" aria-controls="question-help">ⓘ What does this mean?</button>
    <div class="acorn-hc__help-panel" id="question-help" hidden>
      <p>${escapeHtml(parts[0]||'')}</p>
      ${good?`<div class="acorn-hc__good"><strong>What good looks like</strong><p>${escapeHtml(good.trim())}</p></div>`:''}
    </div>`;
 }

 function renderQuestion(q,fromBack=false){
  if(!fromBack&&currentStep)history.push(currentStep);
  currentStep={type:'question',key:q.key};
  const [stage,label]=stageForQuestion(q);
  const risks=q.key==='S01_SELECTED_RISK_CONTROLS'
   ? `<div class="acorn-hc__selected-risks">${(state.profile.risk_flags||[]).map(r=>`<span>${escapeHtml(riskLabels[r]||r)}</span>`).join('')}</div>`
   :'';
  app().innerHTML=`
   ${stageRail(stage,label)}
   <div class="acorn-hc__question-wrap">
    <h2>${escapeHtml(q.text)}</h2>
    ${risks}
    ${helpPanel(q.help)}
    <fieldset class="acorn-hc__answers">
      <legend class="screen-reader-text">Choose an answer</legend>
      ${Object.entries(labels).map(([v,l])=>`<label class="acorn-hc__answer"><input type="radio" name="answer" value="${v}" ${state.answers[q.key]===v?'checked':''}><span>${l}</span></label>`).join('')}
    </fieldset>
    <p class="acorn-hc__save" data-save aria-live="polite">${state.answers[q.key]?'Saved':''}</p>
    <div class="acorn-hc__actions"><button class="acorn-hc__secondary" type="button" data-action="back">Back</button></div>
   </div>`;
  focusHeading();
 }

 function gate(config,fromBack=false){
  if(!fromBack&&currentStep)history.push(currentStep);
  currentStep={type:'gate',gate:config.field};
  const value=state.profile?.[config.field]??'';
  app().innerHTML=`
   ${stageRail(config.stage,config.label)}
   <div class="acorn-hc__question-wrap">
    <h2>${escapeHtml(config.question)}</h2>
    ${helpPanel(config.help)}
    <form data-form="gate" data-field="${config.field}">
      <fieldset class="acorn-hc__answers">
        <legend class="screen-reader-text">Choose an answer</legend>
        ${config.options.map(([v,l])=>`<label class="acorn-hc__answer"><input type="radio" name="gate_value" value="${v}" required ${value===v?'checked':''}><span>${escapeHtml(l)}</span></label>`).join('')}
      </fieldset>
      <p data-save aria-live="polite"></p>
      <div class="acorn-hc__actions"><button class="acorn-hc__secondary" type="button" data-action="back">Back</button></div>
    </form>
   </div>`;
  focusHeading();
 }

 function fireGate(fromBack=false){gate({
  field:'fire_safety_responsibility',stage:2,label:'Fire Safety',
  question:'Are you responsible, fully or partly, for fire safety at any workplace or premises?',
  help:'Fire-safety responsibility can sit with an employer, owner, landlord, occupier or another person with control of the premises. Responsibility can also be shared.',
  options:[['yes','Yes'],['partly','Partly / shared responsibility'],['no','No'],['not_sure','Not sure']]
 },fromBack);}

 function waterGate(fromBack=false){gate({
  field:'water_system_responsibility',stage:3,label:'Premises & Risks',
  question:"Are you responsible, fully or partly, for the building's hot and cold water systems?",
  help:'We ask this so we only show Legionella checks where they may be relevant. Responsibility may sit with your organisation, a landlord, a managing agent or be shared.',
  options:[['yes','Yes'],['partly','Partly / shared responsibility'],['no','No'],['not_sure','Not sure']]
 },fromBack);}

 function asbestosGate(fromBack=false){gate({
  field:'asbestos_responsibility',stage:3,label:'Premises & Risks',
  question:'Are you responsible for maintenance or repair of a non-domestic building built before 2000, or where its age is uncertain?',
  help:'We ask because older buildings may contain asbestos and responsibility for maintenance or repair can bring responsibilities for managing that risk.',
  options:[['yes','Yes'],['partly','Partly / shared responsibility'],['no','No'],['not_sure','Not sure']]
 },fromBack);}

 function riskSelector(fromBack=false){
  if(!fromBack&&currentStep)history.push(currentStep);
  currentStep={type:'risks'};
  const selected=state.profile?.risk_flags||[];
  app().innerHTML=`
   ${stageRail(3,'Your workplace risks')}
   <div class="acorn-hc__question-wrap">
    <h2>Which of these are relevant to your work?</h2>
    <p class="acorn-hc__lead">Select anything that forms a meaningful part of your work. We use this to avoid asking you about risks that do not apply.</p>
    <form data-form="risks">
      <div class="acorn-hc__risk-grid">
       ${Object.entries(riskLabels).map(([v,l])=>`<label class="acorn-hc__risk"><input type="checkbox" name="risks[]" value="${v}" ${selected.includes(v)?'checked':''}><span>${escapeHtml(l)}</span></label>`).join('')}
       <label class="acorn-hc__risk acorn-hc__risk--none"><input type="checkbox" name="none" value="1" ${hasProfile('risk_flags')&&selected.length===0?'checked':''}><span>None of these</span></label>
      </div>
      <p data-save aria-live="polite"></p>
      <div class="acorn-hc__actions"><button class="acorn-hc__secondary" type="button" data-action="back">Back</button><button class="acorn-hc__primary" type="submit">Continue</button></div>
    </form>
   </div>`;
  focusHeading();
 }

 function determineNext(){
  let q=firstUnanswered(coreKeys); if(q)return {type:'question',q};
  if(!hasProfile('fire_safety_responsibility'))return {type:'fire'};
  q=firstUnanswered(fireKeys); if(q)return {type:'question',q};
  if(!hasProfile('water_system_responsibility'))return {type:'water'};
  q=firstUnanswered(legionellaKeys); if(q)return {type:'question',q};
  if(!hasProfile('asbestos_responsibility'))return {type:'asbestos'};
  q=firstUnanswered(asbestosKeys); if(q)return {type:'question',q};
  if(!hasProfile('risk_flags'))return {type:'risks'};
  q=firstUnanswered(specialistKeys); if(q)return {type:'question',q};
  q=firstUnanswered(finalKeys); if(q)return {type:'question',q};
  return {type:'assess'};
 }

 function advance(){
  const next=determineNext();
  if(next.type==='question')return renderQuestion(next.q);
  if(next.type==='fire')return fireGate();
  if(next.type==='water')return waterGate();
  if(next.type==='asbestos')return asbestosGate();
  if(next.type==='risks')return riskSelector();
  assess();
 }

 function renderHistoricalStep(step){
  if(!step)return advance();
  if(step.type==='tailoring')return tailoring();
  if(step.type==='question'){const q=questionByKey(step.key);if(q)return renderQuestion(q,true);}
  if(step.type==='gate'){
   if(step.gate==='fire_safety_responsibility')return fireGate(true);
   if(step.gate==='water_system_responsibility')return waterGate(true);
   if(step.gate==='asbestos_responsibility')return asbestosGate(true);
  }
  if(step.type==='risks')return riskSelector(true);
  advance();
 }

 function headline(){
  currentStep={type:'headline'};
  const h=state.headline;
  const top=h?.top_action;
  app().innerHTML=`
   ${stageRail(4,'Your results')}
   <div class="acorn-hc__results">
    <span class="acorn-hc__eyebrow">Healthcheck complete</span>
    <h2>Here's where things stand</h2>
    <div class="acorn-hc__headline-counts">
      <div class="is-priority"><strong>${h.priority_count}</strong><span>Priority actions</span></div>
      <div class="is-review"><strong>${h.review_count}</strong><span>Worth reviewing</span></div>
      <div class="is-positive"><strong>${h.addressed_count}</strong><span>Areas looking good</span></div>
    </div>
    ${top?`<div class="acorn-hc__top-action"><span class="acorn-hc__eyebrow">${h.priority_count?'Your first priority':'First area to review'}</span><h3>${escapeHtml(top.heading)}</h3><p>${escapeHtml(top.summary)}</p></div>`:`<div class="acorn-hc__top-action"><h3>No obvious gaps were identified in the areas you assessed.</h3></div>`}
    <div class="acorn-hc__contact-gate">
      <h3>Get your complete action plan</h3>
      <p>Enter your details to view your full recommendations and download your report.</p>
      <form data-form="contact">
       <div class="acorn-hc__contact-grid">
        <label>First name<input name="first_name" required autocomplete="given-name"></label>
        <label>Last name<input name="last_name" required autocomplete="family-name"></label>
        <label>Company<input name="company" required autocomplete="organization"></label>
        <label>Work email<input type="email" name="email" required autocomplete="email"></label>
        <label>Telephone<input type="tel" name="telephone" autocomplete="tel"></label>
       </div>
       <fieldset><legend>Would you like Acorn Safety Services to contact you about any of the areas highlighted or arrange a free compliance audit?</legend>
        <label><input type="radio" name="audit_requested" value="yes"> Yes please</label>
        <label><input type="radio" name="audit_requested" value="no" checked> Not at the moment</label>
       </fieldset>
       <label data-audit-postcode hidden>Postcode<input name="postcode" autocomplete="postal-code"></label>
       <label class="acorn-hc__marketing"><input type="checkbox" name="marketing_consent"> I'd also like to receive occasional health and safety guidance and updates from Acorn Safety Services.</label>
       <label class="acorn-hc__honeypot" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
       <button class="acorn-hc__primary" type="submit">View my full action plan</button>
      </form>
    </div>
   </div>`;
  focusHeading();
 }

 async function assess(){
  setSave('Preparing your results…');
  try{state=await request(`/assessments/${token}/assess`,{method:'POST'});headline();}
  catch(e){app().insertAdjacentHTML('beforeend',error(e.message));}
 }

 function setSave(text){const el=app().querySelector('[data-save]');if(el)el.textContent=text;}

 async function mutation(m){
  if(m.type==='answer')return request(`/assessments/${token}/answers/${m.questionKey}`,{method:'PUT',body:JSON.stringify({answer:m.answer})});
  if(m.type==='profile')return request(`/assessments/${token}/profile`,{method:'PATCH',body:JSON.stringify(m.profile)});
 }

 async function updateProfile(patch){
  state=await mutation({type:'profile',profile:patch});
  save({token,lastSeenAt:new Date().toISOString(),pending:[]});
  return state;
 }

 window.addEventListener('online',async()=>{
  try{await flushPendingMutations(mutation);state=await request(`/assessments/${token}`);advance();}
  catch{setSave('Working offline — progress kept on this device');}
 });

 root.addEventListener('change',e=>{
  if(e.target.name==='audit_requested'){
   const required=e.target.value==='yes';
   const telephone=app().querySelector('[name=telephone]');
   const postcode=app().querySelector('[name=postcode]');
   const wrapper=app().querySelector('[data-audit-postcode]');
   telephone.required=required;postcode.required=required;wrapper.hidden=!required;if(!required)postcode.value='';
  }
  if(e.target.name==='none'&&e.target.checked){
   app().querySelectorAll('[name="risks[]"]').forEach(input=>{input.checked=false;});
  }
  if(e.target.name==='risks[]'&&e.target.checked){
   const none=app().querySelector('[name=none]');if(none)none.checked=false;
  }
 });

 root.addEventListener('change',async e=>{
  if(e.target.name!=='gate_value'||busy)return;
  const form=e.target.closest('form[data-form="gate"]');
  if(!form)return;

  busy=true;
  setSave('Saving…');
  const field=form.dataset.field;

  try{
    await updateProfile({[field]:e.target.value});
    setSave('Saved');
    window.setTimeout(()=>advance(),220);
  }catch(err){
    form.insertAdjacentHTML('afterbegin',error(err.message));
  }finally{
    busy=false;
  }
 });

 root.addEventListener('change',async e=>{
  if(e.target.name!=='answer'||busy)return;
  busy=true;setSave('Saving…');
  const q=questionByKey(currentStep?.key);
  if(!q){busy=false;return;}
  const m={type:'answer',questionKey:q.key,answer:e.target.value,clientId:crypto.randomUUID()};
  try{
   state=await mutation(m);
   save({token,lastSeenAt:new Date().toISOString(),pending:[]});
   setSave('Saved');
   window.setTimeout(()=>advance(),220);
  }catch{
   saveLocalPendingMutation(m);
   setSave('Working offline — progress kept on this device');
  }finally{busy=false;}
 });

 root.addEventListener('submit',async e=>{
  e.preventDefault();

  if(e.target.dataset.form==='tailoring'){
   const f=new FormData(e.target);
   setSave('Saving…');
   try{
    state=await updateProfile({jurisdiction:f.get('jurisdiction'),employee_band:f.get('employee_band')});
    history.push({type:'tailoring'});
    currentStep=null;
    advance();
   }catch(err){e.target.insertAdjacentHTML('afterbegin',error(err.message));}
   return;
  }

  if(e.target.dataset.form==='risks'){
   const f=new FormData(e.target);
   const risks=f.get('none')==='1'?[]:f.getAll('risks[]');
   if(!risks.length&&f.get('none')!=='1'){
    e.target.insertAdjacentHTML('afterbegin',error('Select any relevant risks, or choose None of these.'));
    return;
   }
   setSave('Saving…');
   try{await updateProfile({risk_flags:risks});advance();}
   catch(err){e.target.insertAdjacentHTML('afterbegin',error(err.message));}
   return;
  }

  if(e.target.dataset.form==='contact'){
   const f=Object.fromEntries(new FormData(e.target));
   f.audit_requested=f.audit_requested==='yes';
   f.marketing_consent=f.marketing_consent==='on';
   try{
    const result=await request(`/assessments/${token}/complete`,{method:'POST',body:JSON.stringify(f)});
    clear();location.assign(result.report_url);
   }catch(err){
    e.target.insertAdjacentHTML('afterbegin',error(err.message));
    e.target.querySelector('[role=alert]')?.focus();
   }
  }
 });

 root.addEventListener('click',async e=>{
  const action=e.target.dataset.action;

  if(action==='start'||action==='restart'){
   clear();history=[];
   const result=await request('/assessments',{method:'POST'});
   token=result.token;state=result.state;
   save({token,lastSeenAt:new Date().toISOString(),pending:[]});
   tailoring();
  }

  if(action==='continue'){
   const saved=load();token=saved.token;
   state=await request(`/assessments/${token}`);
   await flushPendingMutations(mutation);
   state=await request(`/assessments/${token}`);
   history=[];
   if(!state.profile?.jurisdiction||!state.profile?.employee_band)tailoring();
   else if(state.status==='assessed')headline();
   else{currentStep=null;advance();}
  }

  if(action==='back'){
   const previous=history.pop();
   renderHistoricalStep(previous);
  }

  if(e.target.classList.contains('acorn-hc__help')){
   const panel=app().querySelector('#question-help');
   const open=e.target.getAttribute('aria-expanded')==='true';
   e.target.setAttribute('aria-expanded',String(!open));
   panel.hidden=open;
  }
 });

 landing(Boolean(load()?.token));
}
