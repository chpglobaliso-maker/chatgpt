import fs from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const seed=JSON.parse(fs.readFileSync(path.join(root,'plugin/chp-site/seed.json'),'utf8'));
const origin='https://chpglobaliso.cl';
const pages=[];
for(let i=0;i<seed.length;i+=5){
 await Promise.all(seed.slice(i,i+5).map(async item=>{
  const route=item.type==='page'?(item.slug==='inicio'?'/':`/${item.slug}/`):`/${item.type==='chp_norma'?'norma':'curso'}/${item.slug}/`;
  const r=await fetch(origin+route); const html=await r.text();
  pages.push({route,status:r.status,h1:(html.match(/<h1\b/g)||[]).length,description:/name="description"/.test(html),lang:(html.match(/<html[^>]*lang="([^"]+)/)||[])[1],unrenderedShortcode:/\[chp_/.test(html)});
 }));
}
const formPage=await (await fetch(origin+'/cotizar/')).text();
const nonce=(formPage.match(/name="chp_nonce" value="([^"]+)"/)||[])[1];
if(!nonce)throw Error('Public form nonce missing');
const data={action:'chp_lead',chp_nonce:nonce,nombre:'PRUEBA TÉCNICA CHP — sin gestión comercial',email:'qa@example.invalid',empresa:'PRUEBA DEL SITIO',ubicacion:'Prueba de formulario, Chile',cliente:'Empresa',industria:'Servicios',servicio:'Consulta general',norma:'No aplica',trabajadores:'No aplica',plazo:'Por definir',mensaje:'PRUEBA TÉCNICA AUTORIZADA DEL FORMULARIO. No es una solicitud comercial. Verificar registro privado. Datos ficticios.',consentimiento:'1',website:''};
const submit=async body=>{
 const r=await fetch(origin+'/wp-admin/admin-post.php',{method:'POST',body:new URLSearchParams(body),redirect:'manual'});
 return {status:r.status,location:r.headers.get('location')};
};
const noConsent={...data}; delete noConsent.consentimiento;
const forms={missingConsent:await submit(noConsent),tamperedService:await submit({...data,servicio:'Valor no permitido'}),validAnonymous:await submit(data),duplicateRateLimit:await submit(data)};
const assets=[];
for(const route of ['/wp-content/themes/chp-global-iso/assets/chuquicamata-1600.webp','/wp-content/themes/chp-global-iso/assets/valparaiso-1280.webp','/wp-content/themes/chp-global-iso/assets/inter-latin-wght-normal.woff2','/wp-sitemap.xml']){
 const r=await fetch(origin+route);assets.push({route,status:r.status,type:r.headers.get('content-type')}); await r.arrayBuffer();
}
const report={checkedAt:new Date().toISOString(),pages,assets,forms,limitations:['No recepción de correo comprobada','Sin auditoría WCAG completa ni medición de Core Web Vitals de campo','Respaldo previo completado, sin prueba de restauración en hosting']};
fs.writeFileSync(path.join(root,'docs/live-audit.json'),JSON.stringify(report,null,2));
const ok=pages.every(p=>p.status===200&&p.h1===1&&p.description&&!p.unrenderedShortcode)&&assets.every(a=>a.status===200)&&forms.validAnonymous.location?.includes('/gracias/')&&[forms.missingConsent,forms.tamperedService,forms.duplicateRateLimit].every(x=>x.location?.includes('solicitud=error'));
console.log(JSON.stringify({ok,pages:pages.length,failures:pages.filter(p=>p.status!==200||p.h1!==1||!p.description||p.unrenderedShortcode),assets,forms},null,2));
if(!ok)process.exitCode=1;
