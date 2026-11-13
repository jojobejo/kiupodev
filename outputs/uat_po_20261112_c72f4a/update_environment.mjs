import fs from 'node:fs/promises';
import {FileBlob,SpreadsheetFile} from '@oai/artifact-tool';
const root='/Applications/XAMPP/xamppfiles/htdocs/kiupobaru';
const out=root+'/outputs/uat_po_20261112_c72f4a';
const file=out+'/UAT_Seluruh_Alur_PO_20261112.xlsx';
const m=JSON.parse(await fs.readFile(root+'/docs/uat/20261112/environment/manifest.json','utf8'));
if(m.readiness_result!=='PASS')throw Error('UAT readiness has not passed');
const wb=await SpreadsheetFile.importXlsx(await FileBlob.load(file));
const before={};
for(const name of ['Skenario','Peran','Temuan']){
 const address=name==='Skenario'?'A1:O86':name==='Peran'?'A1:I47':'A1:M26';
 const range=wb.worksheets.getItem(name).getRange(address);
 before[name]={address,values:range.values,formulas:range.formulas};
}
for(const [name,range] of [['Ringkasan','A1:H22'],['Prasyarat','A2:I9'],['Data Uji','A2:H10'],['Persetujuan','A2:I12']]){
 const preview=await wb.render({sheetName:name,range,scale:1,format:'png'});
 await fs.writeFile(out+'/before-environment-'+name.replaceAll(' ','-')+'.png',new Uint8Array(await preview.arrayBuffer()));
}
await fs.copyFile(file,m.runtime_root+'/baseline-workbook.xlsx');
const date=new Date('2026-11-12T00:00:00Z');
const pre=wb.worksheets.getItem('Prasyarat');
const actual={
 PR01:m.database+'; koneksi default dan testing aman. '+m.url.replace('/uat','/uat-health'),
 PR02:m.url+'; login '+m.login_url+'; kode cocok gate P6.',
 PR03:'9 akun sintetis siap. Kredensial di AKUN_UAT.md lokal, di luar folder web.',
 PR04:'Akses PIC lain, Kadep beda departemen, Keuangan-negatif dan sesi privat diuji.',
 PR05:'Fixture bertanda '+m.run+'; kode/status awal di portal dan manifest. N-SISA adalah fixture parsial terkontrol.',
 PR06:'Supplier, Shipment To A/B, material, vendor dan file TXT pendukung UAT tersedia.',
 PR07:'Gate struktur P2–P6 PASS. Baseline sebelum UAT tersimpan; sumber asli dan baris lama tetap.'
};
const prIds=pre.getRange('A7:A17').values;
for(let i=0;i<prIds.length;i++){const text=actual[prIds[i][0]];if(text)pre.getRange('E'+(7+i)+':H'+(7+i)).values=[['Siap','Admin UAT (persiapan teknis)',date,text]];}
pre.getRange('A4').values=[['Lingkungan teknis sudah disiapkan. Lengkapi prasyarat manusia, jadwal dan keputusan penerimaan sebelum sesi UAT.']];
const data=wb.worksheets.getItem('Data Uji'),fixtures=data.getRange('A7:G18').values;
for(let i=0;i<fixtures.length;i++){
 const [group,purpose]=fixtures[i];let text='';
 if(group==='K-BASE')text=purpose.includes('A')?'U26A_KA':'U26A_KB';
 else if(group==='N-BELI')text=m.masters[purpose.endsWith('A')?'N-BELI':'N-BELI-B'].sku;
 else if(purpose==='Invoice utama')text='Buat melalui P01 pada '+m.fixture_states['J-BASE'].code;
 else if(purpose==='Invoice sen')text='Buat melalui P04 pada '+m.fixture_states['J-SEN'].code;
 else if(group==='J-BASE'&&purpose==='Scope jasa')text=m.fixture_states['J-BASE'].code+' (scope draft)';
 else if(m.masters[group])text=m.masters[group].sku+(m.fixture_states[group]?' / '+m.fixture_states[group].code:'');
 data.getRange('H'+(7+i)).values=[[text]];
}
data.getRange('A4').values=[['Master dan fixture telah dibuat untuk UAT lokal. Invoice baru tetap dibuat melalui skenario, bukan ditandai sudah diuji.']];
const sign=wb.worksheets.getItem('Persetujuan');
const roleAlias={'PIC':'PIC-A','Kadep':'Kadep-GA','Purchasing':'Purchasing','Direktur Operasional':'DirOps','Direktur':'Direktur','Admin':'Admin'};
const accounts=Object.fromEntries(m.accounts.map(a=>[a.alias,a]));
for(let i=0;i<6;i++){const role=sign.getRange('A'+(7+i)).values[0][0];sign.getRange('D'+(7+i)).values=[[accounts[roleAlias[role]].username]];}
wb.recalculate();
for(const [name,saved] of Object.entries(before)){
 const range=wb.worksheets.getItem(name).getRange(saved.address),values=range.values,formulas=range.formulas;
 if(JSON.stringify(formulas)!==JSON.stringify(saved.formulas))throw Error('Unrelated formulas changed: '+name);
 for(let row=0;row<saved.values.length;row++)for(let column=0;column<saved.values[row].length;column++){
  if(saved.formulas[row]?.[column])continue;
  if(JSON.stringify(values[row][column]??'')!==JSON.stringify(saved.values[row][column]??''))throw Error('Unrelated input changed: '+name);
 }
}
const summary=wb.worksheets.getItem('Ringkasan');
if(summary.getRange('D6').values[0][0]!==0||summary.getRange('C14').values[0][0]!=='Belum diputuskan')throw Error('UAT results were incorrectly advanced');
if(summary.getRange('F8').values[0][0]!==7)throw Error('Readiness prerequisites did not recalculate');
const errors=await wb.inspect({kind:'match',searchTerm:'#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!',options:{useRegex:true,maxResults:20},summary:'UAT environment update error scan'});
await fs.writeFile(out+'/environment-formula-scan.ndjson',errors.ndjson);
for(const [name,range] of [['Ringkasan','A1:H22'],['Prasyarat','E6:I10'],['Data Uji','F6:H12'],['Persetujuan','A6:E12']]){
 const preview=await wb.render({sheetName:name,range,scale:1,format:'png'});
 await fs.writeFile(out+'/environment-'+name.replaceAll(' ','-')+'.png',new Uint8Array(await preview.arrayBuffer()));
}
const result=await SpreadsheetFile.exportXlsx(wb);await result.save(file);
console.log(JSON.stringify({updated:file,technical_prerequisites_ready:7,scenario_results:'80 Belum Diuji',unrelated_tables_preserved:true}));
