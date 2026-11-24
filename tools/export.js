const fs=require('fs'),vm=require('vm');
const code=fs.readFileSync('/home/claude/biogoods/assets/js/data.js','utf8');
const ctx={window:{}};ctx.window=ctx;vm.createContext(ctx);vm.runInContext(code+';this.__BG=BG;',ctx);
const BG=ctx.__BG;
// teljes lista a rejtettekkel együtt: futtassuk újra hidden nélkül
const ctx2={window:{}};ctx2.window=ctx2;vm.createContext(ctx2);
vm.runInContext(code.replace(/BG\.hidden = \[/,'BG.hidden = [];BG.__h=[')+';this.__BG=BG;',ctx2);
const ALL=ctx2.__BG;
const out={categories:BG.categories,occasions:BG.occasions,products:ALL.products,hidden:BG.hidden,adatlap:BG.adatlap,photos:BG.photos,whiteImg:BG.whiteImg,towns:BG.towns,areas:BG.areas,cutoffHour:BG.cutoffHour,featured:BG.featured};
fs.writeFileSync('bg-data.json',JSON.stringify(out,null,1));
console.log('products',ALL.products.length,'visible',BG.products.length,'towns',BG.towns.length,'hidden',BG.hidden);
console.log(Object.keys(BG).join(' '));
