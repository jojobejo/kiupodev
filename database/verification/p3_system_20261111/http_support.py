from http.client import HTTPConnection
from http.cookies import SimpleCookie
from pathlib import Path
from urllib.parse import quote, urlencode
import json
import secrets
import subprocess
import tempfile

root = Path(tempfile.mkdtemp(prefix='kiupo-p3-http-output.'))
db = 'kiucoid_kiupo_merge_p0_20261110'
mysql = ['/Applications/XAMPP/xamppfiles/bin/mysql','--no-defaults','-uroot','--protocol=SOCKET',
         '--socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock','--database='+db,'-NB']
php = '/Applications/XAMPP/xamppfiles/bin/php'
tag = 'P3T' + secrets.token_hex(4).upper()
buyer, director = tag+'P', tag+'D'
secret = secrets.token_hex(24)
pwd = subprocess.check_output([php, '-r', 'echo password_hash(trim(stream_get_contents(STDIN)), PASSWORD_BCRYPT);'],
                              input=secret, text=True)

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

