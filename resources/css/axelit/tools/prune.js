// Axelit style.css dan panelga keraksiz bo'limlarni olib tashlaydi.
// Foydalanish: node prune.js <axelit-style.css> <chiqish.css>   (npm i css-tree)
const csstree=require('css-tree');const fs=require('fs');
let css=fs.readFileSync(process.argv[2] || 'style.css','utf8');
css=css.replace(/body\[class="ltr dark"\]/g,'body.dark').replace(/\[class="ltr dark"\]/g,'body.dark');
const DROP=new RegExp([
 'ribbon','-bullet\\b','\\.bullet','\\[dir=rtl\\]','\\[dir="rtl"\\]','\\.rtl\\b','html\\[dir','horizontal-sidebar','box-layout','customizer','\\.landing','\\.dark-section',
 '\\.fc-','\\.fc\\b','calendar','kanban','\\.email','\\.mail-','\\.chat','\\.blog','gallery','pricing','\\.faq','coming-soon','\\.error-page','\\.maintenance','sitemap','file-manager','bookmark',
 '\\.todo','\\.team-','\\.checkout','\\.cart-','\\.wishlist','facebook','twitter','pinterest','linkedin','reddit','whatsapp','gmail','telegram','youtube','vimeo','behance','github','skype','snapchat',
 '^\\.(gold|warm|happy|nature|cold|hot|default)\\b','body\\[text=','prism','language-','slick','cross-shadow','countdown','dual-listbox','\\.tour','swal','sweet','trumbowyg','\\.editor','filepond','jvm','leaflet','weather','header-cloud','\\.cloud-','\\.lock-screen','\\.two-step','\\.invoice','\\.post-','\\.social-','\\.shape-','vertical-sitemap','\\.tree-','draggable','scrollpy','\\.irs','noUi','touchspin','typeahead','select2','dataTables','\\.dt-','\\.ql-','\\.note-','\\.rating','br-theme','bs-stepper','masonry','\\.video-','animated-icon','flag-icon','header-apps','header-cart','header-language','\\.apexcharts','\\.app-calendar','\\.gallery','\\.product-details','\\.add-product','\\.cart\\b'
].join('|'));
// NOTE: dashboard widget families (country-card, customer-list, product-*) dropped here; re-added below as a separate file if needed
const ast=csstree.parse(css);
let kept=0, dropped=0;
csstree.walk(ast,{visit:'Rule',enter(node,item,list){
  const sels=csstree.generate(node.prelude).split(',');
  if(sels.every(s=>DROP.test(s))){ list.remove(item); dropped++; } else kept++;
}});
// remove empty at-rules
csstree.walk(ast,{visit:'Atrule',enter(node,item,list){ if(node.block && node.name==='media' && node.block.children.isEmpty){ list.remove(item);} }});
const out=csstree.generate(ast);
fs.writeFileSync(process.argv[3] || 'axelit.core.css',out);
console.log('kept',kept,'dropped',dropped,'bytes',out.length);
