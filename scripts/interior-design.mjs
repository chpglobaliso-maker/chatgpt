import fs from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..'),theme=path.join(root,'theme/chp-global-iso');
const e=s=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
const block=s=>`<!-- wp:html -->${s}<!-- /wp:html -->`;
const registry=JSON.parse(fs.readFileSync(path.join(root,'content/photography.json'),'utf8'));
const photos=registry.photos;
const pages=JSON.parse(fs.readFileSync(path.join(root,'content/pages.json'),'utf8'));
const seed=JSON.parse(fs.readFileSync(path.join(root,'plugin/chp-site/seed.json'),'utf8'));
const mapping={nosotros:['reloncavi','puerto-montt'],servicios:['antofagasta','solar'],'implementacion-iso':['solar','agroindustria'],'normas-iso':['taltal'],'auditorias':['digitalizacion','antofagasta'],'preparacion-certificacion':['antofagasta'],'acompanamiento':['solar'],'erp':['servidores'],'gestion-documental':['digitalizacion'],'academia':['biblioteca'],'capacitacion-empresas':['biblioteca'],'capacitacion-personas':['biblioteca'],'industrias':['reloncavi'],'metodologia':['antofagasta'],'proyectos':['solar'],'recursos':['biblioteca','digitalizacion'],'preguntas-frecuentes':['taltal'],'cotizar':['puerto-montt'],contacto:['puerto-montt']};
const normPhotos={'iso-9001':'antofagasta','iso-14001':'taltal','iso-45001':'solar','iso-22000':'agroindustria','iso-27001':'servidores','iso-31000':'antofagasta','iso-19011':'digitalizacion','iso-26000':'reloncavi'};
function figure(key,wide=false){const p=photos[key];if(!p)throw Error('Missing photo '+key);return `<figure class="chp-editorial-photo${wide?' wide':''}"><img src="{{theme}}/${p.file}" srcset="{{theme}}/${p.small} 720w, {{theme}}/${p.file} ${p.width}w" sizes="(max-width: 800px) 100vw, ${wide?'1120px':'50vw'}" width="${p.width}" height="${p.height}" alt="${e(p.alt)}" loading="${wide?'lazy':'eager'}" decoding="async" style="object-position:${p.position}"><figcaption><span>${e(p.label)} · Foto de contexto</span><span><a href="${e(p.source)}" rel="external">${e(p.author)}</a> · <a href="${p.licenseUrl}" rel="license">${p.license}</a> · <a href="/creditos/">Créditos</a></span></figcaption></figure>`;}
function hero(d,key){return block(`<section class="chp-editorial-hero"><div class="chp-editorial-intro"><p class="chp-breadcrumb"><a href="/">Inicio</a> / ${e(d.title)}</p><p class="chp-eyebrow">${e(d.eyebrow)}</p><h1>${e(d.headline)}</h1><p class="chp-intro">${e(d.intro)}</p><span class="chp-editorial-rule" aria-hidden="true"></span></div>${figure(key)}</section>`);}
const replaceHero=(content,newHero)=>content.replace(/<!-- wp:group \{"className":"chp-hero"[\s\S]*?<\/section><!-- \/wp:group -->/,newHero);
function credits(){return block(`<section class="chp-section chp-photo-credits"><h2>Fotografías reales de las páginas interiores</h2><p>${e(registry.notice)} Versiones redimensionadas y convertidas a WebP, con encuadres de presentación mediante CSS. Las adaptaciones de fotografías con licencia CC BY-SA se distribuyen bajo la misma licencia indicada en cada ficha.</p><div class="chp-license-grid">${Object.entries(photos).map(([k,p])=>`<article><h3>${e(p.label)}</h3><p>${e(p.author)} · <a href="${p.licenseUrl}" rel="license">${p.license}</a></p><a href="${e(p.source)}" rel="external">Fuente y ficha de derechos</a><p class="chp-note">${e(p.changes)}</p></article>`).join('')}</div><p>La selección de estas fotografías no implica respaldo de sus autores o instituciones a CHP GLOBAL ISO. No se incorporaron fotografías con restricciones de uso comercial identificadas durante la revisión.</p></section>`);}
for(const item of seed){
 if(item.slug==='inicio')continue;
 let key=mapping[item.slug]?.[0];
 if(item.type==='chp_norma')key=normPhotos[item.slug]||'solar';
 if(item.type==='chp_curso')key='biblioteca';
 if(!key&&item.slug!=='creditos')continue;
 const isPage=item.type==='page';
 const file=path.join(theme,'templates',isPage?`page-${item.slug}.html`:`single-${item.type}-${item.slug}.html`);
 let template=isPage?fs.readFileSync(file,'utf8'):`<!-- wp:template-part {"slug":"header","tagName":"header"} /--><!-- wp:group {"tagName":"main","anchor":"main-content","layout":{"type":"default"}} --><main class="wp-block-group" id="main-content">${item.content}</main><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->`;
 let preview=item.content;
 if(item.slug==='creditos'){template=template.replace('</main>',credits()+'</main>');preview+=credits();}
 else if(item.slug==='contacto'){
  const banner=block(`<div class="chp-contact-city">${figure(key,true)}</div>`);template=template.replace('<main class="wp-block-group" id="main-content">','<main class="wp-block-group" id="main-content">'+banner);preview=banner+fs.readFileSync(path.join(root,'dist/contacto/index.html'),'utf8').split('<main id="main-content">')[1].split('</main>')[0];
 }else{
  const d=pages.find(x=>x.slug===item.slug)||{title:item.title,eyebrow:item.area||'SISTEMAS DE GESTIÓN',headline:item.title,intro:item.excerpt};
  const head=hero(d,key);template=replaceHero(template,head);preview=replaceHero(preview,head);
  const secondary=mapping[item.slug]?.[1];
  if(secondary){const band=block(`<section class="chp-editorial-break" aria-label="Contexto productivo de Chile">${figure(secondary,true)}</section>`);template=template.replace('<!-- wp:html --><section class="chp-band">',band+'<!-- wp:html --><section class="chp-band">');preview=preview.replace('<!-- wp:html --><section class="chp-band">',band+'<!-- wp:html --><section class="chp-band">');}
  if(item.slug==='industrias'){
   const gallery=block(`<section class="chp-sector-gallery"><div><p class="chp-eyebrow">CHILE PRODUCTIVO</p><h2>Distintas industrias.<br>Una mirada desde los procesos.</h2></div><div class="chp-sector-grid">${[['reloncavi','Acuicultura y alimentos'],['solar','Energía e infraestructura'],['agroindustria','Agroindustria']].map(([k,t])=>`<article>${figure(k)}<h3>${t}</h3></article>`).join('')}</div></section>`);template=template.replace('<!-- wp:html --><section class="chp-band">',gallery+'<!-- wp:html --><section class="chp-band">');preview=preview.replace('<!-- wp:html --><section class="chp-band">',gallery+'<!-- wp:html --><section class="chp-band">');
  }
 }
 template=template.replace('class="wp-block-group" id="main-content"','class="wp-block-group chp-interior" id="main-content"').replaceAll('{{theme}}','/wp-content/themes/chp-global-iso');
 fs.writeFileSync(file,template);
 const slug=isPage?item.slug:(item.type==='chp_norma'?'norma/':'curso/')+item.slug;
 const dest=path.join(root,'dist',slug,'index.html');
 let rendered=fs.readFileSync(dest,'utf8');preview=preview.replaceAll('{{theme}}','/theme').replace(/\[chp_catalog type="(chp_\w+)"\]/g,'<p>Catálogo funcional en WordPress.</p>').replaceAll('[chp_form]','<p class="chp-section">Formulario funcional en WordPress.</p>');rendered=rendered.replace(/<main id="main-content">[\s\S]*?<\/main>/,`<main class="chp-interior" id="main-content">${preview}</main>`).replace('</head>','<link rel="stylesheet" href="/theme/interior.css"></head>');fs.writeFileSync(dest,rendered);
}
fs.cpSync(path.join(root,'content/photography.json'),path.join(theme,'assets/photos/photography.json'));
fs.cpSync(theme,path.join(root,'dist/theme'),{recursive:true});
console.log('Fotografía corporativa real aplicada a 19 páginas interiores y 11 fichas de normas/cursos; créditos publicados.');
