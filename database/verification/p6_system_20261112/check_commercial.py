from html.parser import HTMLParser
from http.client import HTTPConnection
from http.cookies import SimpleCookie
from pathlib import Path
from urllib.parse import quote, urlencode
import json
import secrets
import subprocess
import tempfile

root = Path(tempfile.mkdtemp(prefix='kiupo-p6-commercial-output.'))
db = 'kiucoid_kiupo_merge_p0_20261110'
mysql = ['/Applications/XAMPP/xamppfiles/bin/mysql','--no-defaults','-uroot','--protocol=SOCKET',
         '--socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock','--database='+db,'-NB']
php = '/Applications/XAMPP/xamppfiles/bin/php'
tag = 'P6T' + secrets.token_hex(4).upper()
supplier = tag
template, other = tag+'A', tag+'B'
buyer, director = tag+'P', tag+'D'
recipient = 'P1 Shipment Recipient ' + tag
address = 'P1 Test Address ' + tag
secret = secrets.token_hex(24)
pwd = subprocess.check_output([php, '-r', 'echo password_hash(trim(stream_get_contents(STDIN)), PASSWORD_BCRYPT);'],
                              input=secret, text=True)
code = None

def sql(q):
    r = subprocess.run(mysql, input=q, text=True, capture_output=True)
    if r.returncode:
        raise AssertionError(r.stderr)
    return r.stdout.strip()

def lit(s):
    return "'" + s.replace("'", "''") + "'"

def run_http(path, cookie=None, data=None, ajax=False):
    conn = HTTPConnection('127.0.0.1',18761,timeout=25)
    headers = {}
    if cookie:
        headers['Cookie'] = cookie
    if ajax:
        headers['X-Requested-With'] = 'XMLHttpRequest'
    if data is not None:
        headers['Content-Type'] = 'application/x-www-form-urlencoded'
    conn.request('POST' if data is not None else 'GET',path,urlencode(data) if data is not None else None,headers)
    res=conn.getresponse()
    body=res.read().decode('utf-8',errors='replace')
    h=res.getheaders()
    conn.close()
    (root/'last-response.html').write_text(body)
    return res.status, dict(h), body, h

def login(who):
    status, h, _, headers=run_http('/Auth/process', data={'user_isi':who,'pass_isi':secret})
    assert status in (302,303,307) and h.get('Location','').endswith('/dashboard'), (status,h.get('Location'))
    jar=SimpleCookie()
    for k,v in headers:
        if k.lower()=='set-cookie':
            jar.load(v)
    return 'kiupo_p1_test='+jar['kiupo_p1_test'].value

def html(path, cookie):
    status,h,body,_=run_http(path,cookie)
    assert status==200,(path,status,h.get('Location'),body[:450])
    assert 'A Database Error Occurred' not in body and 'An uncaught Exception' not in body,path
    return body

def send(path, cookie, fields):
    status,h,body,_=run_http(path,cookie,fields,True)
    assert status==200,(path,status,h.get('Location'),body[:450])
    try:
        return json.loads(body)
    except ValueError:
        raise AssertionError((path, body[:450]))

class Draft(HTMLParser):
    def __init__(self, page):
        super().__init__()
        self.inputs={}
        self.selected=[]
        self.shipment=False
        self.feed(page)
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=='input' and a.get('id'):
            self.inputs[a['id']]=a.get('value','')
        if tag=='select':
            self.shipment=a.get('id')=='shipment_template'
        if tag=='option' and self.shipment and 'selected' in a:
            self.selected.append(a.get('value'))
    def handle_endtag(self, tag):
        if tag=='select':
            self.shipment=False

try:
    assert sql('SELECT DATABASE();') == db
    sql(f"""
START TRANSACTION;
INSERT INTO tbpo_user (kode_user,nama_user,username,password,aksess_lv,departement) VALUES
('{buyer}','P1 Test Purchasing','{buyer}','{pwd}',2,'PURCHASING'),
('{director}','P1 Test Direktur','{director}','{pwd}',3,'DIREKTUR');
INSERT INTO tbpo_suplier (kd_suplier,nama_suplier,alamat_suplier,no_telpon,no_fax,email,gbr_logo)
VALUES ('{supplier}','P1 Test Supplier','P1 Test Street','','','','');
INSERT INTO tbpo_notetemplate (kd_nt_template,nama_note,shipment_to,alamat_ship,cp_shipment,no_cp,ket_1,ket_2) VALUES
('{template}','P1 Selected Template','{recipient}','{address}','P1 Contact','0210000000','',''),
('{other}','P1 Other Template','P1 Other Recipient','P1 Other Address','','','','');
INSERT INTO tbpo_tmp_item
(kode_barang,kd_user,nama_barang,kode_suplier,satuan,qty,isi,kemasan,qty_kecil,harga_satuan,harga_satuan_exclude,harga_satuan_kecil,harga_satuan_kecil_exclude,total_harga,keterangan_harga_ppn)
VALUES ('{tag}I1','{buyer}','P1 Test Item One','{supplier}','PCS',2,1,1,2,10000,10000,10000,10000,20000,'exclude'),
       ('{tag}I2','{buyer}','P1 Test Item Two','{supplier}','PCS',3,1,1,3,5000,5000,5000,5000,15000,'exclude');
COMMIT;
""")
    category,unit,location=sql("SELECT kat_barang,satuan,kd_lokasi FROM tbpo_barang_nk WHERE kat_barang<>'' AND satuan>0 AND kd_lokasi>0 LIMIT 1;").split('\t')
    sql(f"INSERT INTO tbpo_barang_nk (kd_barang,kd_br_adm,kat_barang,nama_barang,descnk,satuan,gbr_barang,qrcode_path,qrcode_data,inputer,create_at,last_updated,kd_lokasi) VALUES ('{tag}I1','{tag}SYS','{category}','P6 shared commercial SKU','P6 ledger isolation',{unit},'Karisma.png','','','{buyer}',NOW(),'{buyer}',{location});")
    p=login(buyer)
    d=login(director)
    path='/purchase/sup/'+supplier
    page=html(path,p)
    assert 'Simpan Shipment To' in page and 'Ajukan PO' in page
    draft=Draft(page)
    assert not draft.selected,draft.selected
    code=draft.inputs['kd_po_isi']
    number=draft.inputs['po_isi']
    assert supplier in code and number
    print('PASS login Purchasing/Direktur dan halaman draft',flush=True)

    assert run_http('/rekam_po',p)[0]==405
    data={'suplier':supplier,'shipment_template':template,'save_draft':'1'}
    forbidden=run_http('/rekam_po',d,data,True)
    assert forbidden[0]==403,forbidden[0]
    assert send('/rekam_po',p,dict(data,shipment_template=tag+'X'))['msg']=='error'
    assert sql(f"SELECT COUNT(*) FROM tbpo_tmp_po_shipment WHERE kd_suplier='{supplier}';")=='0'
    assert send('/rekam_po',p,data)['msg']=='success'
    assert sql(f"SELECT kd_printout_note FROM tbpo_tmp_po_shipment WHERE kd_suplier='{supplier}';")==template
    page=html(path,p)
    assert Draft(page).selected==[template]
    assert sql(f"SELECT COUNT(*) FROM tbpo_po WHERE kd_suplier='{supplier}';")=='0'
    (root/'draft-rendered.html').write_text(page)
    print('PASS simpan dan buka ulang draft, template palsu dan peran lain ditolak',flush=True)

    submit={'nopo':number,'tgl':'2026-11-12','tmpo':'30','gdg':'P1','kdpo':code,'jml':'2',
            'suplier':supplier,'harga':'35000','tax':'11','shipment_template':''}
    assert send('/rekam_po',p,submit)['msg']=='error'
    assert sql(f"SELECT COUNT(*) FROM tbpo_tmp_item WHERE kode_suplier='{supplier}';")=='2'
    assert send('/rekam_po',p,dict(submit,shipment_template=template))=={'msg':'success','nopo':number}
    assert sql(f"SELECT status,kd_printout_note,ROUND(total_harga),ROUND(hrg_pajak) FROM tbpo_po WHERE kd_po={lit(code)};")==f'ON PROGRESS\t{template}\t35000\t3850'
    assert sql(f"SELECT COUNT(*),SUM(qty) FROM tbpo_detail_po WHERE kd_po={lit(code)};")=='2\t5'
    assert sql(f"SELECT (SELECT COUNT(*) FROM tbpo_tmp_item WHERE kode_suplier='{supplier}'), (SELECT COUNT(*) FROM tbpo_tmp_po_shipment WHERE kd_suplier='{supplier}');")=='0\t0'
    assert send('/rekam_po',p,dict(submit,shipment_template=template))['msg']=='error'
    print('PASS pengajuan, dua detail, total 35000, PPN 3850, draft dibersihkan, retry ditolak',flush=True)
    assert sql(f"SELECT s.nama_suplier FROM tbpo_po p JOIN tbpo_suplier s ON s.id_suplier=p.id_suplier WHERE p.kd_po={lit(code)};")=='P1 Test Supplier'
    sql(f"INSERT INTO tbpo_suplier (kd_suplier,nama_suplier,alamat_suplier,no_telpon,no_fax,email,gbr_logo) VALUES ('{supplier}','P1 Alternate Supplier','P1 Other Street','','','','');")


    quoted=quote(code,safe='')
    detail='/detailPO/'+quoted
    page=html(detail,d)
    assert 'btn-konfirmasi' in page and recipient in page
    approval='/konfirmasiOrder/'+quoted+'/'+quote(buyer,safe='')
    assert run_http(approval,p)[0]==403
    reply=run_http(approval,d)
    assert reply[0] in (302,303,307), (reply[0], reply[1].get('Location'), reply[2][:500])
    assert sql(f"SELECT status,acc_with,kd_printout_note FROM tbpo_po WHERE kd_po={lit(code)};")==f'ACC DIREKTUR\t{director}\t{template}'
    reply=run_http(approval,d)
    assert reply[0] in (302,303,307), (reply[0], reply[1].get('Location'), reply[2][:500])
    assert sql(f"SELECT COUNT(*) FROM tbpo_note_direktur WHERE kd_po={lit(code)} AND isi_note='PO ACCEPT';")=='1'
    supplier_print='/print_po_supplier/'+quoted
    assert run_http(supplier_print,p)[0] in (302,303,307)
    result=send('/poconfirmacc/'+quoted,p,{})
    assert result['success'],result
    assert sql(f"SELECT status FROM tbpo_po WHERE kd_po={lit(code)};")=='ON DELIVERY'
    assert not send('/poconfirmacc/'+quoted,p,{})['success']
    print('PASS approval Direktur, audit aktor sesi dan konfirmasi ON DELIVERY',flush=True)

    page=html(detail,p)
    (root/'detail-rendered.html').write_text(page)
    assert recipient in page and 'Print PO Supplier - Include' in page
    assert 'id="modalSelectTemplate"' not in page
    for route in [supplier_print+'/include',supplier_print+'/exclude','/print_po/'+quoted+'/include','/printOrder/'+quoted]:
        page=html(route,p)
        assert recipient in page and address in page and 'P1 Other Recipient' not in page and number in page,route
        assert page.count(recipient)==1 and 'P1 Test Supplier' in page and 'P1 Alternate Supplier' not in page,route
    assert run_http('/shipment_to',p,{'update_shipment':code,'template_isi':other})[0] in (302,303,307)
    assert sql(f"SELECT kd_printout_note FROM tbpo_po WHERE kd_po={lit(code)};")==template
    print('PASS cetak internal, supplier Include/Exclude dan cetak lama memakai Shipment To yang dipilih',flush=True)
    print('PASS PO baru tetap terikat ke pemasok yang dipilih saat kode yang sama ditambah kemudian',flush=True)
    sql(f"DELETE FROM tbpo_suplier WHERE kd_suplier='{supplier}' AND nama_suplier='P1 Alternate Supplier';")


    # Simulate a legacy header in this fixture only.
    sql(f"UPDATE tbpo_po SET kd_printout_note='' WHERE kd_po={lit(code)} AND kd_suplier='{supplier}';")
    assert 'id="modalSelectTemplate"' in html(detail,p)
    status,h,_,_=run_http('/shipment_to',p,{'update_shipment':code,'template_isi':template,'print_mode':'exclude'})
    assert status in (302,303,307) and h.get('Location','').endswith(supplier_print+'/exclude'),(status,h.get('Location'))
    assert recipient in html(supplier_print+'/exclude',p)
    assert send('/onhandpo_ajax',p,{'kdpo':code})['success']
    assert sql(f"SELECT status FROM tbpo_po WHERE kd_po={lit(code)};")=='DONE'
    assert not send('/onhandpo_ajax',p,{'kdpo':code})['success']
    assert sql(f"SELECT COUNT(*),SUM(tr_qty),COUNT(DISTINCT satuan),MIN(satuan) FROM tbpo_transaksi WHERE kd_po_nk={lit(code)} AND kd_akun='11411';")=='2\t5\t1\t2'
    print('PASS fallback Shipment To PO lama dan penerimaan dua item tepat sekali',flush=True)
    assert sql(f"SELECT qty_ready FROM v_stockbarangnk WHERE kode_barangs='{tag}I1';")=='0'
    print('PASS P6 commercial account 11411 does not enter noncommercial stock for the same SKU',flush=True)
    sql(f"INSERT INTO tbpo_tmp_item (kode_barang,kd_user,nama_barang,kode_suplier,satuan,qty,harga_satuan,total_harga) VALUES ('{tag}I1','{buyer}','P1 Item','{supplier}','PCS',1,10,10);")
    tmp_id=sql(f"SELECT id_tmp FROM tbpo_tmp_item WHERE kode_suplier='{supplier}';")
    assert send('/rekam_po',p,data)['msg']=='success'
    assert run_http('/hapusChart/'+tmp_id+'/'+supplier,p)[0] in (302,303,307)
    assert sql(f"SELECT (SELECT COUNT(*) FROM tbpo_tmp_item WHERE kode_suplier='{supplier}'),(SELECT COUNT(*) FROM tbpo_tmp_po_shipment WHERE kd_suplier='{supplier}');")=='0\t0'
    print('PASS hapus item terakhir membersihkan pilihan shipment',flush=True)
finally:
    q=[]
    if code is not None and supplier in code:
        q.extend(f"DELETE FROM {t} WHERE {k}={lit(code)};"
                 for t,k in [('tbpo_transaksi','kd_po_nk'),('tbpo_note_direktur','kd_po'),('tbpo_detail_po','kd_po'),('tbpo_po','kd_po')])
    q += [f"DELETE FROM {table} WHERE {key}='{supplier}';" for table,key in [
        ('tbpo_tmp_po_shipment','kd_suplier'),('tbpo_tmp_tax','kd_suplier'),('tbpo_tmp_item','kode_suplier'),('tbpo_suplier','kd_suplier')]]
    q += [f"DELETE FROM tbpo_barang_nk WHERE kd_barang='{tag}I1';"]
    q += [f"DELETE FROM tbpo_notetemplate WHERE kd_nt_template IN ('{template}','{other}');",
          f"DELETE FROM tbpo_user WHERE kode_user IN ('{buyer}','{director}');"]
    sql('START TRANSACTION;\n'+'\n'.join(q)+'\nCOMMIT;')
    print('Fixture uji dibersihkan dari database uji.',flush=True)
