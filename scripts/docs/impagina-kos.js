// Impagina un Markdown nel formato dei documenti per KOS e Cerved/ProWeb (come calendario-sprint-linee-a-b.docx):
// testata «GRUPPO KOS», titolo, sottotitolo linee A e B, firma, intestazione «Documento riservato», numero di pagina,
// Arial, tabelle con intestazione #1F5C6E. Uso: node scripts/docs/impagina-kos.js <file.md> <uscita.docx> ["Danilo Bernabeo · data"]
// Argomenti 5 e 6 (facoltativi): immagine PNG al posto del contenuto incorporato della scheda e dimensioni LxA in px.
// Il Markdown è l’export della scheda (prima riga «# Titolo», terza riga la firma della scheda, che viene sostituita).
const fs=require('fs');
const d=require('docx');
const {ImageRun,Document,Packer,Paragraph,TextRun,Table,TableRow,TableCell,WidthType,ShadingType,AlignmentType,BorderStyle,Header,Footer,PageNumber,LevelFormat,ExternalHyperlink,VerticalAlign}=d;
const F='Arial', INK='20262B', TEAL='1F5C6E', GREY='6B7278', LINE='B8C7CC', RED='9C3B2E';
let md=fs.readFileSync(process.argv[2]||'nota.md','utf8')
  .replace(/\[Convenzione degli identificativi del dato strutturato\]\([^)]*\)/,'Convenzione degli identificativi del dato strutturato (documento allegato)')
  .replace('| Scheda condivisa |','| Documento allegato |');
const lines=md.split('\n');
// inline parser: **bold**, `code`, [t](u)
function runs(s,base={}){
  const out=[]; const re=/(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\))/g; let i=0,m;
  const T=(t,o={})=>new TextRun({text:t,font:F,color:INK,size:base.size||21,bold:base.bold,...o});
  while((m=re.exec(s))){ if(m.index>i) out.push(T(s.slice(i,m.index)));
    const x=m[0];
    if(x.startsWith('**')) out.push(...runs(x.slice(2,-2),{...base,bold:true}));
    else if(x.startsWith('`')) out.push(new TextRun({text:x.slice(1,-1),font:'Consolas',color:TEAL,size:(base.size||21)-2,bold:base.bold}));
    else { const t=x.match(/\[([^\]]+)\]\(([^)]+)\)/); out.push(new ExternalHyperlink({link:t[2],children:[T(t[1],{color:TEAL,underline:{}})]})); }
    i=m.index+x.length; }
  if(i<s.length) out.push(T(s.slice(i)));
  return out;
}
const body={spacing:{after:120,before:0,line:290},alignment:AlignmentType.JUSTIFIED};
const H2=t=>new Paragraph({keepNext:true,border:{bottom:{style:BorderStyle.SINGLE,color:LINE,size:6,space:4}},spacing:{after:140,before:300},children:[new TextRun({text:t,font:F,bold:true,color:TEAL,size:28})]});
const W=9638;
const WIDTHS={5:[1750,2050,3238,1500,1100],3:[2000,4238,3400],4:[1700,2400,2938,2600],2:[2200,7438]};
const WBOT=[3638,2000,2000,2000];
function table(rows){
  const n=rows[0].length; let cw=WIDTHS[n];
  if(rows[0][0].startsWith('Bot')) cw=WBOT;
  if(rows[0][0].startsWith('Entità')) cw=[1800,2050,2238,3550];
  if(rows[0][0]==='Sito'&&n===3) cw=[1700,2700,5238];
  const mar={top:60,left:110,bottom:60,right:110};
  const b={style:BorderStyle.SINGLE,color:LINE,size:4};
  const numeric=c=>/^[\d.,]+$/.test(c.trim());
  return new Table({width:{size:W,type:WidthType.DXA},columnWidths:cw,
    borders:{top:b,left:b,bottom:b,right:b,insideHorizontal:b,insideVertical:b},
    rows:rows.map((r,ri)=>new TableRow({tableHeader:ri==0,cantSplit:true,children:r.map((c,ci)=>new TableCell({
      width:{size:cw[ci],type:WidthType.DXA},margins:mar,verticalAlign:VerticalAlign.CENTER,
      shading:ri==0?{fill:TEAL,type:ShadingType.CLEAR,color:'auto'}:undefined,
      children:[new Paragraph({alignment:(ri>0&&numeric(c))?AlignmentType.RIGHT:AlignmentType.LEFT,
        children: ri==0? [new TextRun({text:c.replace(/`/g,''),font:F,bold:true,color:'FFFFFF',size:19})] : runs(c,{size:19,bold:ci==0})})]}))}))});
}
const kids=[]; let i=0; let prevList=false;
// title block
const title=lines[0].replace(/^# /,'');
kids.push(new Paragraph({spacing:{after:80},children:[new TextRun({text:'GRUPPO KOS',font:F,bold:true,color:RED,size:18,characterSpacing:50})]}));
kids.push(new Paragraph({spacing:{after:60},children:[new TextRun({text:title,font:F,bold:true,color:TEAL,size:40})]}));
kids.push(new Paragraph({spacing:{after:60},children:[new TextRun({text:'Linee A e B · Ottimizzazione SEO/GEO di kosgroup.com e anniazzurri.it',font:F,color:INK,size:24})]}));
kids.push(new Paragraph({border:{bottom:{style:BorderStyle.SINGLE,color:LINE,size:6,space:8}},spacing:{after:240},children:[new TextRun({text:(process.argv[4]||'Danilo Bernabeo · 30 ottobre 2026'),font:F,color:GREY,size:19})]}));
i=3;
while(i<lines.length){
  const l=lines[i];
  if(!l.trim()){i++;continue;}
  const isList=/^(\d+\. |- )/.test(l); const wasList=prevList; prevList=isList; const _w=wasList;
  if(l.startsWith('## ')){kids.push(H2(l.slice(3)));i++;continue;}
  if(l.startsWith('```')){ i++; const code=[]; while(i<lines.length&&!lines[i].startsWith('```')){code.push(lines[i]);i++;} i++;
    code.forEach((c,k)=>kids.push(new Paragraph({shading:{fill:'F3F6F7',type:ShadingType.CLEAR,color:'auto'},spacing:{after:0,line:240},indent:{left:120,right:120},
      border:k==0?{top:{style:BorderStyle.SINGLE,color:LINE,size:4,space:4}}:(k==code.length-1?{bottom:{style:BorderStyle.SINGLE,color:LINE,size:4,space:4}}:undefined),
      children:[new TextRun({text:c.replace(/ /g,'\u00a0'),font:'Consolas',size:14,color:INK})]})));
    kids.push(new Paragraph({spacing:{after:120},children:[]})); continue; }
  if(l.startsWith('&#91;embedded content')){ const img=process.argv[5]; if(img&&fs.existsSync(img)){ const [w,h]=(process.argv[6]||'600x494').split('x').map(Number);
      kids.push(new Paragraph({alignment:AlignmentType.CENTER,spacing:{after:120},children:[new ImageRun({type:'png',data:fs.readFileSync(img),transformation:{width:w,height:h}})]})); } i++; continue; }
  if(l.startsWith('|')){ if(/^(\d+\.|- )/.test(lines[i-2]||'')) kids.push(new Paragraph({spacing:{after:60},children:[]})); const rows=[]; while(i<lines.length&&lines[i].startsWith('|')){ const cells=lines[i].slice(1,-1).split(' | ').map(s=>s.trim()); if(!/^-+$/.test(cells[0])) rows.push(cells); i++; }
    kids.push(table(rows)); kids.push(new Paragraph({spacing:{after:60},children:[]})); continue; }
  if(/^\d+\. /.test(l)){ kids.push(new Paragraph({numbering:{reference:'num',level:0},spacing:{after:70,line:290},alignment:AlignmentType.JUSTIFIED,children:runs(l.replace(/^\d+\. /,''))})); i++; continue; }
  if(l.startsWith('- ')){ kids.push(new Paragraph({numbering:{reference:'bul',level:0},spacing:{after:70,line:290},alignment:AlignmentType.JUSTIFIED,children:runs(l.slice(2))})); i++; continue; }
  kids.push(new Paragraph({...body,spacing:{...body.spacing,before:_w?180:0},children:runs(l)})); i++;
}
const doc=new Document({
  creator:'Danilo Bernabeo',title,
  styles:{default:{document:{run:{font:F,size:21,color:INK}}}},
  numbering:{config:[
    {reference:'bul',levels:[{level:0,format:LevelFormat.BULLET,text:'•',alignment:AlignmentType.LEFT,style:{paragraph:{indent:{left:360,hanging:240}}}}]},
    {reference:'num',levels:[{level:0,format:LevelFormat.DECIMAL,text:'%1.',alignment:AlignmentType.LEFT,style:{paragraph:{indent:{left:360,hanging:300}}}}]}]},
  sections:[{properties:{page:{size:{width:11906,height:16838},margin:{top:1300,right:1134,bottom:1134,left:1134,header:560,footer:500}}},
    headers:{default:new Header({children:[new Paragraph({children:[new TextRun({text:'Documento riservato - destinato a Gruppo KOS e Cerved/ProWeb.',font:F,color:GREY,size:15})]})]})},
    footers:{default:new Footer({children:[new Paragraph({alignment:AlignmentType.RIGHT,children:[new TextRun({children:[PageNumber.CURRENT],font:F,color:GREY,size:16})]})]})},
    children:kids}]});
Packer.toBuffer(doc).then(b=>fs.writeFileSync(process.argv[3]||'nota-di-consegna-sprint-1.docx',b));
