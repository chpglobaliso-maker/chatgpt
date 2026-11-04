import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import Parser from 'php-parser';
const root=path.resolve(import.meta.dirname,'..');
const read=p=>fs.readFileSync(path.join(root,p),'utf8');
const seed=JSON.parse(read('plugin/chp-site/seed.json'));
assert.equal(seed.filter(x=>x.type==='page').length,24);
assert.equal(seed.filter(x=>x.type==='chp_norma').length,8);
assert.equal(seed.filter(x=>x.type==='chp_curso').length,3);
assert.equal(new Set(seed.map(x=>x.type+'/'+x.slug)).size,35);
const targets=new Set(seed.map(x=>(x.type==='page'?(x.slug==='inicio'?'/':`/${x.slug}/`):`/${x.type==='chp_norma'?'norma':'curso'}/${x.slug}/`)));
for(const item of seed) {
 assert.equal((item.content.match(/<h1\b/g)||[]).length,1,item.slug+' heading');
 assert(item.excerpt.length>15,item.slug+' description');
 for(const [,url] of item.content.matchAll(/href="(\/[^"]*)"/g)) assert(targets.has(url),`${item.slug}: broken link ${url}`);
 const stack=[];
 for(const token of item.content.matchAll(/<!-- (\/?wp:[a-z-]+)\b[^>]*-->/g)) {
  if(token[0].endsWith('/-->'))continue;
  if(token[1].startsWith('/'))assert.equal(stack.pop(),token[1].slice(1),item.slug+' blocks');
  else stack.push(token[1]);
 }
 assert.equal(stack.length,0,item.slug+' unclosed blocks');
}
const parser=new Parser({parser:{phpVersion:'8.1'},ast:{withPositions:true}});
for(const file of ['plugin/chp-site/chp-site.php','theme/chp-global-iso/functions.php'])parser.parseCode(read(file),file);
JSON.parse(read('theme/chp-global-iso/theme.json'));
for(const a of ['inter-latin-wght-normal.woff2','manrope-latin-wght-normal.woff2','chuquicamata-1600.webp','valparaiso-1280.webp'])assert(fs.statSync(path.join(root,'theme/chp-global-iso/assets',a)).size>1000);
console.log('PASS: 35 fichas, enlaces internos, H1, bloques equilibrados, 2 PHP analizados, theme.json y activos locales.');
