import fs from 'node:fs/promises';
import path from 'node:path';
import {Workbook,SpreadsheetFile} from '@oai/artifact-tool';

const root='/Applications/XAMPP/xamppfiles/htdocs/kiupobaru';
const out=path.dirname(new URL(import.meta.url).pathname);
const d=JSON.parse(await fs.readFile(`${root}/docs/uat/20261112/uat_cases.json`,'utf8'));
const wb=Workbook.create();
const names=['Ringkasan','Skenario','Peran','Prasyarat','Data Uji','Temuan','Persetujuan'];
const sh=Object.fromEntries(names.map(n=>[n,wb.worksheets.add(n)]));
const navy='#263E59',ink='#243247',amber='#FFF3CF',gray='#EEF2F6',muted='#5C6C80';
const first=7,last=first+d.cases.length-1,ckLast=first+d.checks.length-1,prLast=first+d.prereqs.length-1;
function col(i){let s='';for(i++;i>0;i=Math.floor((i-1)/26))s=String.fromCharCode(65+(i-1)%26)+s;return s;}
function base(name,title,headers,rows,widths,height=54){
 const s=sh[name],end=first+rows.length-1,wide=col(headers.length-1);
 s.showGridLines=false;
 s.getRange(`A1:${wide}${Math.max(end,10)}`).format.font={name:'Arial',size:10,color:ink};
 s.getRange(`A1:${wide}${Math.max(end,10)}`).format.verticalAlignment='center';
 s.getRange('A2').values=[[title]];s.getRange('A2').format.font={name:'Arial',size:15,bold:true,color:ink};
 s.getRange(`A3:${wide}3`).format.borders={bottom:{style:'thin',color:navy}};
 s.getRange(`A6:${wide}6`).values=[headers];
 s.getRange(`A6:${wide}6`).format={fill:navy,font:{name:'Arial',size:10,bold:true,color:'#FFFFFF'},wrapText:true,horizontalAlignment:'center',verticalAlignment:'center',rowHeight:32};
 s.getRange(`A${first}:${wide}${end}`).values=rows;
 s.getRange(`A${first}:${wide}${end}`).format.wrapText=true;
 rows.forEach((row,index)=>{
  const lines=Math.max(...row.map((v,i)=>Math.ceil(String(v??'').length/Math.max(10,widths[i]*.85))));
  s.getRange(`A${first+index}:${wide}${first+index}`).format.rowHeight=Math.max(32,Math.min(height,lines*15+12));
 });
 s.getRange(`A${first}:${wide}${end}`).format.verticalAlignment='top';
 widths.forEach((w,i)=>s.getRange(`${col(i)}6:${col(i)}${end}`).format.columnWidth=w);
 const table=s.tables.add(`A6:${wide}${end}`,true,'UAT'+name.replaceAll(' ',''));
 table.style='TableStyleLight1';
 s.freezePanes.freezeRows(6);s.freezePanes.freezeColumns(name==='Skenario'?3:1);
 return s;
}
function input(s,range,values){s.getRange(range).format.fill=amber;s.getRange(range).format.font.color='#1F5190';if(values)s.getRange(range).dataValidation={rule:{type:'list',values}};}
function status(s,range){
 s.getRange(range).conditionalFormats.add('containsText',{text:'Gagal',format:{fill:'#FCE6E4',font:{color:'#A12325',bold:true}}});
 s.getRange(range).conditionalFormats.add('containsText',{text:'Terhambat',format:{fill:'#FFF0CC',font:{color:'#7D5500'}}});
 s.getRange(range).conditionalFormats.add('containsText',{text:'Tidak sesuai',format:{fill:'#FCE6E4',font:{color:'#A12325'}}});
}

const s=base('Skenario','Skenario dan hasil UAT',
 ['ID','Modul','Peran utama','Jenis','Fixture','Prasyarat','Langkah pengujian','Hasil yang diharapkan','Hasil UAT','Tester','Tanggal','Hasil aktual','Bukti / tautan','ID temuan','Kelengkapan'],
 d.cases.map(c=>[c.id,c.module,c.role,c.kind,c.fixture,c.pre,c.steps,c.expected,'Belum Diuji','','','','','','']),
 [10,24,23,14,19,26,62,62,19,22,15,48,34,18,25],126);
s.getRange('A4').values=[['Isi kolom kuning setelah diuji. Pada uji negatif, Lulus berarti penolakan berjalan sesuai harapan.']];
input(s,`I7:I${last}`,['Belum Diuji','Dalam Proses','Lulus','Gagal','Terhambat']);
input(s,`J7:N${last}`);s.getRange(`K7:K${last}`).setNumberFormat('dd/mm/yyyy');
s.getRange(`O7:O${last}`).formulas=d.cases.map((c,i)=>{const r=first+i;return [`=IF(I${r}="Lulus",IF(AND(J${r}<>"",K${r}<>"",L${r}<>"",M${r}<>""),"Lengkap","Lengkapi bukti"),IF(I${r}="Gagal",IF(N${r}="","Isi ID temuan","Tindak lanjut"),I${r}))`];});
status(s,`I7:I${last}`);
s.getRange(`O7:O${last}`).conditionalFormats.add('containsText',{text:'Lengkapi',format:{fill:'#FFF0CC',font:{color:'#7D5500'}}});

const cks=base('Peran','Checklist per peran',
 ['ID','Peran','Hal yang harus dipastikan','ID skenario','Hasil checklist','Tester','Tanggal','Bukti / catatan','Kelengkapan'],
 d.checks.map(c=>[c.id,c.role,c.action,c.refs,'Belum Dicek','','','','']),[10,24,66,40,21,23,15,52,24],66);
cks.getRange('A4').values=[['Filter kolom Peran untuk checklist masing-masing. Hasil checklist diisi setelah bukti skenario diperiksa.']];
input(cks,`E7:E${ckLast}`,['Belum Dicek','Sesuai','Tidak sesuai','Terhambat']);input(cks,`F7:H${ckLast}`);cks.getRange(`G7:G${ckLast}`).setNumberFormat('dd/mm/yyyy');
cks.getRange(`I7:I${ckLast}`).formulas=d.checks.map((c,i)=>{const r=first+i;return [`=IF(E${r}="Sesuai",IF(AND(F${r}<>"",G${r}<>"",H${r}<>""),"Lengkap","Lengkapi bukti"),E${r})`];});status(cks,`E7:E${ckLast}`);

const pre=base('Prasyarat','Prasyarat sebelum UAT',
 ['ID','Pemilik','Prasyarat','Yang harus disiapkan','Status','Pemeriksa','Tanggal','Bukti / nilai aktual','Kelengkapan'],
 d.prereqs.map(p=>[...p,'Belum Dicek','','','','']),[10,22,28,85,21,23,15,65,24],78);
pre.getRange('A4').values=[['Selesaikan prasyarat sebelum menjalankan transaksi UAT. Nama tester, akun aktual dan URL belum ditetapkan.']];
input(pre,`E7:E${prLast}`,['Belum Dicek','Siap','Terhambat']);input(pre,`F7:H${prLast}`);pre.getRange(`G7:G${prLast}`).setNumberFormat('dd/mm/yyyy');
pre.getRange(`I7:I${prLast}`).formulas=d.prereqs.map((p,i)=>{const r=first+i;return [`=IF(E${r}="Siap",IF(AND(F${r}<>"",G${r}<>"",H${r}<>""),"Lengkap","Lengkapi bukti"),E${r})`];});
pre.getRange(`A${prLast+3}`).values=[['Acuan penyusunan']];pre.getRange(`A${prLast+3}`).format.font.bold=true;
d.sources.forEach((source,i)=>pre.getRange(`D${prLast+4+i}`).values=[[source]]);
pre.getRange(`D${prLast+4}:H${prLast+7}`).format.columnWidth=30;
// Preserve the working table widths; sources use wrapped wide column D.
pre.getRange(`D6:D${prLast+7}`).format.columnWidth=85;
pre.getRange(`D${prLast+4}:D${prLast+7}`).format.wrapText=true;
pre.getRange(`D${prLast+4}:D${prLast+7}`).format.rowHeight=35;

const data=base('Data Uji','Data sintetis untuk disiapkan Admin',
 ['Fixture','Kegunaan','Alias barang/dokumen','Qty','Harga fixture (Rp)','Satuan','Catatan kontrol','Kode aktual run'],
 d.fixtures.map(f=>[...f,'']),[22,31,26,12,22,16,78,30],65);
data.getRange('A4').values=[['Alias belum dibuat di database. Gunakan salinan terpisah untuk cabang; jangan mengganti data historis.']];
data.getRange(`D7:D${first+d.fixtures.length-1}`).setNumberFormat('#,##0.00');data.getRange(`E7:E${first+d.fixtures.length-1}`).setNumberFormat('"Rp "#,##0.00');input(data,`H7:H${first+d.fixtures.length-1}`);

const defect=base('Temuan','Pencatatan dan retest temuan',
 ['ID temuan','ID skenario','Keparahan','Status temuan','Pemilik perbaikan','Ringkasan temuan','Langkah reproduksi','Hasil retest','Bukti / tautan','Keputusan minor','Penyetuju / validator','Tanggal','Penutupan'],
 Array.from({length:20},()=>Array(13).fill('')),[17,15,16,22,25,55,62,50,32,40,27,15,24],80);
defect.getRange('A4').values=[['Kritis/Mayor harus diperbaiki dan lulus retest. Minor yang diterima memerlukan alasan dan persetujuan tercatat.']];
input(defect,'A7:L26');input(defect,'C7:C26',['Kritis','Mayor','Minor']);input(defect,'D7:D26',['Baru','Diperbaiki','Retest','Lulus Retest','Diterima Minor']);
defect.getRange('B7:B26').dataValidation={rule:{type:'list',formula1:`INDIRECT("'Skenario'!$A$7:$A$${last}")`}};
defect.getRange('L7:L26').setNumberFormat('dd/mm/yyyy');
defect.getRange('M7:M26').formulas=Array.from({length:20},(_,i)=>{const r=7+i;return [`=IF(A${r}="","",IF(AND(B${r}<>"",OR(C${r}="Kritis",C${r}="Mayor",C${r}="Minor"),E${r}<>"",F${r}<>"",G${r}<>""),IF(AND(D${r}="Lulus Retest",H${r}<>"",I${r}<>"",K${r}<>"",L${r}<>""),"Selesai",IF(AND(C${r}="Minor",D${r}="Diterima Minor",J${r}<>"",K${r}<>"",L${r}<>""),"Selesai","Terbuka")),"Terbuka"))`];});

const sign=base('Persetujuan','Persetujuan hasil per peran',
 ['Peran','Nama','Jabatan / unit','Alias akun UAT','Keputusan','Catatan','Bukti / tautan','Tanggal','Kelengkapan'],
 d.roles.map(role=>[role,'','','','Belum Ditinjau','','','','']),[25,25,28,25,23,64,42,15,25],70);
sign.getRange('A4').values=[['Diisi perwakilan peran setelah skenario, checklist dan temuan ditinjau. Ini bukan hasil regresi otomatis P6.']];
input(sign,'B7:H12');input(sign,'E7:E12',['Belum Ditinjau','Setuju','Tunda']);sign.getRange('H7:H12').setNumberFormat('dd/mm/yyyy');
sign.getRange('I7:I12').formulas=d.roles.map((role,i)=>{const r=7+i;return [`=IF(E${r}="Setuju",IF(AND(B${r}<>"",D${r}<>"",G${r}<>"",H${r}<>""),"Lengkap","Lengkapi bukti"),E${r})`];});

const summary=sh.Ringkasan;summary.showGridLines=false;summary.tabColor=navy;
summary.getRange('A1:H36').format.font={name:'Arial',size:10,color:ink};summary.getRange('A1:H36').format.verticalAlignment='center';
summary.getRange('A2').values=[['UAT seluruh alur PO']];summary.getRange('A2').format.font={name:'Arial',size:15,bold:true,color:ink};
summary.getRange('A3:H3').format.borders={bottom:{style:'thin',color:navy}};
summary.getRange('A4').values=[['Pelaksanaan']];summary.getRange('B4').formulas=[[`=IF(COUNTIFS(Skenario!$I$7:$I$${last},"Belum Diuji")=COUNTA(Skenario!$A$7:$A$${last}),"Belum dimulai","Dalam evaluasi")`]];
summary.getRange('A6:H6').values=[['Skenario',null,'Lulus',null,'Gagal',null,'Terhambat',null]];
summary.getRange('B6').formulas=[[`=COUNTA(Skenario!$A$7:$A$${last})`]];
summary.getRange('D6').formulas=[[`=COUNTIFS(Skenario!$I$7:$I$${last},"Lulus")`]];
summary.getRange('F6').formulas=[[`=COUNTIFS(Skenario!$I$7:$I$${last},"Gagal")`]];
summary.getRange('H6').formulas=[[`=COUNTIFS(Skenario!$I$7:$I$${last},"Terhambat")`]];
summary.getRange('A8:H8').values=[['Bukti lengkap',null,'Checklist lengkap',null,'Prasyarat lengkap',null,'Temuan terbuka',null]];
summary.getRange('B8').formulas=[[`=COUNTIFS(Skenario!$O$7:$O$${last},"Lengkap")`]];
summary.getRange('D8').formulas=[[`=COUNTIFS(Peran!$I$7:$I$${ckLast},"Lengkap")`]];
summary.getRange('F8').formulas=[[`=COUNTIFS(Prasyarat!$I$7:$I$${prLast},"Lengkap")`]];
summary.getRange('H8').formulas=[['=COUNTIFS(Temuan!$M$7:$M$26,"Terbuka")']];
summary.getRange('A10').values=[['Dokumentasi penutupan']];
summary.getRange('C10').formulas=[[`=IF(AND(B6=D6,B8=B6,D8=${d.checks.length},F8=${d.prereqs.length},H8=0,COUNTIFS(Persetujuan!$I$7:$I$12,"Lengkap")=6),"Lengkap untuk keputusan akhir","Belum siap ditutup")`]];
summary.getRange('A12').values=[['Keputusan koordinator']];summary.getRange('C12').values=[['Belum diputuskan']];input(summary,'C12',['Belum diputuskan','Diterima','Ditunda']);
summary.getRange('E12').values=[['Koordinator']];summary.getRange('F11:H11').values=[['Nama','Tanggal','Bukti keputusan']];input(summary,'F12:H12');summary.getRange('G12').setNumberFormat('dd/mm/yyyy');
summary.getRange('A14').values=[['Status penerimaan']];summary.getRange('C14').formulas=[['=IF(AND(C10="Lengkap untuk keputusan akhir",C12="Diterima",F12<>"",G12<>"",H12<>""),"UAT diterima",IF(C12="Diterima","Lengkapi prasyarat dan bukti",IF(C12="Ditunda","UAT ditunda","Belum diputuskan")))']];
summary.getRange('A16:H16').values=[['Peran utama','Jumlah','Belum Diuji','Dalam Proses','Lulus','Gagal','Terhambat','Bukti lengkap']];
summary.getRange('A16:H16').format={fill:navy,font:{name:'Arial',bold:true,color:'#FFFFFF'},wrapText:true,rowHeight:30,horizontalAlignment:'center'};
d.roles.forEach((role,i)=>{const r=17+i;summary.getRange(`A${r}`).values=[[role]];
 summary.getRange(`B${r}`).formulas=[[`=COUNTIFS(Skenario!$C$7:$C$${last},A${r})`]];
 ['Belum Diuji','Dalam Proses','Lulus','Gagal','Terhambat'].forEach((st,j)=>summary.getRange(`${col(j+2)}${r}`).formulas=[[`=COUNTIFS(Skenario!$C$7:$C$${last},$A${r},Skenario!$I$7:$I$${last},${col(j+2)}$16)`]]);
 summary.getRange(`H${r}`).formulas=[[`=COUNTIFS(Skenario!$C$7:$C$${last},A${r},Skenario!$O$7:$O$${last},"Lengkap")`]];
});
summary.getRange('A24').values=[['Cara mengisi']];summary.getRange('A24').format.font.bold=true;
const instructions=[
 '1. Selesaikan Prasyarat. Isi kode aktual pada Data Uji. Setiap cabang memakai fixture terpisah.',
 '2. Jalankan Skenario, isi hasil aktual dan bukti. Filter Peran untuk checklist tiap tester.',
 '3. Catat kegagalan di Temuan, lakukan retest dan simpan persetujuan per peran.',
 '4. Semua skenario wajib Lulus dengan bukti. Keputusan akhir tetap diisi koordinator.',
 'P6 telah lulus teknis: 33 kelompok HTTP dan 210 pemeriksaan CLI. UAT pengguna belum dijalankan.',
 'Tanggal penyusunan 12 November 2026. Fokus struktur/sistem. Migrasi data ditunda.'
];instructions.forEach((t,i)=>summary.getRange(`A${25+i}`).values=[[t]]);
summary.getRange('A17:H22').format.rowHeight=25;
summary.getRange('A25:H30').format.rowHeight=24;
summary.getRange('A6:H8').format.rowHeight=32;
['A10:H10','A12:H12','A14:H14'].forEach(r=>{summary.getRange(r).format.wrapText=true;summary.getRange(r).format.rowHeight=45;});
['B6','D6','F6','H6','B8','D8','F8','H8'].forEach(a=>{summary.getRange(a).format.font.bold=true;summary.getRange(a).format.horizontalAlignment='center';});
[29,15,31,19,26,25,20,35].forEach((w,i)=>summary.getRange(`${col(i)}1:${col(i)}36`).format.columnWidth=w);
summary.getRange('B4:D4').format.wrapText=true;summary.getRange('B4:D4').format.rowHeight=32;

wb.recalculate();
const initial=summary.getRange('B6').values[0][0];
if(initial!==d.cases.length)throw Error(`Wrong scenario total ${initial}`);
if(summary.getRange('C14').values[0][0]!=='Belum diputuskan')throw Error('UAT falsely accepted');

// Exercise a partial completion and a premature coordinator decision, then restore.
s.getRange('I7').values=[['Lulus']];summary.getRange('C12').values=[['Diterima']];wb.recalculate();
if(summary.getRange('D6').values[0][0]!==1 || summary.getRange('B8').values[0][0]!==0 || summary.getRange('C14').values[0][0]==='UAT diterima')throw Error('Missing evidence incorrectly accepted');
s.getRange('J7:N7').values=[['TEST_QA',new Date('2026-11-12T00:00:00Z'),'QA actual','QA evidence','']];wb.recalculate();
if(summary.getRange('B8').values[0][0]!==1 || summary.getRange('C10').values[0][0]!=='Belum siap ditutup')throw Error('Open prerequisites hidden');
sh.Temuan.getRange('A7:D7').values=[['QA-01','A01','Kritis','Baru']];wb.recalculate();
if(summary.getRange('H8').values[0][0]!==1)throw Error('Critical defect disappeared');
// All result inputs are populated in a disposable in-memory state. An open critical
// defect must still prevent acceptance; completing its retest must unlock the gate.
const qaDate=new Date('2026-11-12T00:00:00Z');
s.getRange(`I7:N${last}`).values=d.cases.map(()=>['Lulus','TEST_QA',qaDate,'QA actual','QA evidence','']);
cks.getRange(`E7:H${ckLast}`).values=d.checks.map(()=>['Sesuai','TEST_QA',qaDate,'QA evidence']);
pre.getRange(`E7:H${prLast}`).values=d.prereqs.map(()=>['Siap','TEST_QA',qaDate,'QA evidence']);
sign.getRange('B7:H12').values=d.roles.map(()=>['TEST_QA','QA role','QA alias','Setuju','','QA evidence',qaDate]);
summary.getRange('F12:H12').values=[['TEST_QA',qaDate,'QA evidence']];wb.recalculate();
if(summary.getRange('C14').values[0][0]==='UAT diterima')throw Error('Open critical defect accepted');
defect.getRange('A7:L7').values=[['QA-01','A01','Kritis','Lulus Retest','TEST_QA','QA finding','QA reproduction','QA retest','QA evidence','','TEST_QA',qaDate]];wb.recalculate();
if(summary.getRange('C14').values[0][0]!=='UAT diterima')throw Error('Complete acceptance gate did not update');
defect.getRange('C7:D7').values=[['Minor','Diterima Minor']];defect.getRange('J7').values=[['']];wb.recalculate();
if(summary.getRange('H8').values[0][0]!==1)throw Error('Minor accepted without decision');
defect.getRange('J7').values=[['QA minor acceptance rationale']];wb.recalculate();
if(summary.getRange('C14').values[0][0]!=='UAT diterima')throw Error('Documented minor acceptance failed');
s.getRange(`I7:N${last}`).values=d.cases.map(()=>['Belum Diuji','','','','','']);
cks.getRange(`E7:H${ckLast}`).values=d.checks.map(()=>['Belum Dicek','','','']);
pre.getRange(`E7:H${prLast}`).values=d.prereqs.map(()=>['Belum Dicek','','','']);
sign.getRange('B7:H12').values=d.roles.map(()=>['','','','Belum Ditinjau','','','']);
summary.getRange('C12').values=[['Belum diputuskan']];summary.getRange('F12:H12').clear({applyTo:'contents'});defect.getRange('A7:L7').clear({applyTo:'contents'});wb.recalculate();
if(summary.getRange('D6').values[0][0]!==0 || summary.getRange('H8').values[0][0]!==0 || summary.getRange('C14').values[0][0]!=='Belum diputuskan')throw Error('QA state was not cleared');

const inspect=await wb.inspect({kind:'table',range:'Ringkasan!A4:H22',include:'values,formulas',tableMaxRows:20,tableMaxCols:8,maxChars:4800});
await fs.writeFile(`${out}/inspection.ndjson`,inspect.ndjson);
const errors=await wb.inspect({kind:'match',searchTerm:'#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!',options:{useRegex:true,maxResults:30},summary:'Formula error scan',maxChars:2500});
await fs.writeFile(`${out}/formula-errors.ndjson`,errors.ndjson);
for(const [name,range] of [['Ringkasan','A1:H30'],['Skenario','A2:H9'],['Peran','A2:I9'],['Prasyarat','A2:I9'],['Data Uji','A2:H10'],['Temuan','A2:M9'],['Persetujuan','A2:I12']]){
 const png=await wb.render({sheetName:name,range,scale:1,format:'png'});
 await fs.writeFile(`${out}/preview-${name.replaceAll(' ','-')}.png`,new Uint8Array(await png.arrayBuffer()));
}
const xlsx=await SpreadsheetFile.exportXlsx(wb);await xlsx.save(`${out}/UAT_Seluruh_Alur_PO_20261112.xlsx`);
await fs.writeFile(`${out}/verification.json`,JSON.stringify({scenarios:d.cases.length,role_checklists:d.checks.length,prerequisites:d.prereqs.length,roles:d.roles.length,scenario_status:'Belum Diuji',manual_acceptance:'Belum diputuskan',recalculation_tests:'PASS',rendered_sheets:names},null,2));
console.log(JSON.stringify({file:`${out}/UAT_Seluruh_Alur_PO_20261112.xlsx`,scenarios:d.cases.length,checks:d.checks.length}));
