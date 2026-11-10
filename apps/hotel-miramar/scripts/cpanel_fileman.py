"""cPanel File Manager helper via UAPI + BasicAuth.
No credentials hardcoded - use env vars CPANEL_HOST/USER/PASS.
Commands:
  list <dir>
  get <fullpath>              -> prints file content to stdout
  download <remote> <local>   -> saves to local file
  save <remote> <localfile>   -> uploads local file content to remote (Fileman::save_file_content)
  raw <api_path> k=v ...
"""
import os, sys, ssl, json, base64, urllib.request, urllib.parse

HOST = os.environ.get("CPANEL_HOST", "https://cityofgoldestate.com:2083").rstrip("/")
USER = os.environ.get("CPANEL_USER", "")
PASS = os.environ.get("CPANEL_PASS", "")

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def api(path, params=None):
    if not USER or not PASS:
        print("Missing CPANEL_USER/CPANEL_PASS env vars", file=sys.stderr)
        sys.exit(2)
    q = ("?" + urllib.parse.urlencode(params)) if params else ""
    url = f"{HOST}{path}{q}"
    creds = base64.b64encode(f"{USER}:{PASS}".encode()).decode()
    req = urllib.request.Request(url, headers={"Authorization": f"Basic {creds}"})
    with urllib.request.urlopen(req, context=ctx, timeout=60) as r:
        return json.loads(r.read().decode("utf-8", errors="replace"))

def api_post(path, params):
    creds = base64.b64encode(f"{USER}:{PASS}".encode()).decode()
    data = urllib.parse.urlencode(params).encode()
    req = urllib.request.Request(f"{HOST}{path}", data=data,
        headers={"Authorization": f"Basic {creds}", "Content-Type": "application/x-www-form-urlencoded"})
    with urllib.request.urlopen(req, context=ctx, timeout=60) as r:
        return json.loads(r.read().decode("utf-8", errors="replace"))

def list_dir(path):
    res = api("/execute/Fileman/list_files", {"dir": path, "show_hidden": 1})
    if res.get("status") != 1:
        print(json.dumps(res, indent=2)[:8000])
        return
    print(f"--- {path} : {len(res.get('data') or [])} entries ---")
    for d in res.get("data") or []:
        t = d.get("type", "?")
        name = d.get("file", "?")
        size = d.get("humansize", d.get("size", ""))
        print(f"{t:6} {size:>10}  {name}")

def get_content(remote):
    # Fileman::get_file_content needs dir + file separately
    d = remote.rsplit("/", 1)
    directory, filename = (d[0] or "/", d[1]) if len(d) == 2 else ("/", d[0])
    res = api("/execute/Fileman/get_file_content", {"dir": directory, "file": filename})
    if res.get("status") != 1:
        print(json.dumps(res, indent=2)[:8000], file=sys.stderr)
        sys.exit(1)
    return res["data"]["content"]

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(1)
    cmd = sys.argv[1]
    if cmd == "list":
        target = sys.argv[2] if len(sys.argv) > 2 else f"/home/{USER}"
        print(f"Listing {target} on {HOST} as {USER}\n")
        list_dir(target)
    elif cmd == "get":
        print(get_content(sys.argv[2]), end="")
    elif cmd == "download":
        remote, local = sys.argv[2], sys.argv[3]
        content = get_content(remote)
        os.makedirs(os.path.dirname(local) or ".", exist_ok=True)
        with open(local, "w", encoding="utf-8", newline="") as f:
            f.write(content)
        print(f"saved {len(content)} chars -> {local}")
    elif cmd == "save":
        remote, local = sys.argv[2], sys.argv[3]
        with open(local, "r", encoding="utf-8") as f:
            content = f.read()
        d = remote.rsplit("/", 1)
        directory, filename = (d[0] or "/", d[1]) if len(d) == 2 else ("/", d[0])
        res = api_post("/execute/Fileman/save_file_content",
                       {"dir": directory, "file": filename, "content": content})
        print(json.dumps(res, indent=2)[:4000])
    elif cmd == "raw":
        path = sys.argv[2]
        params = dict(p.split("=", 1) for p in sys.argv[3:])
        print(json.dumps(api(path, params), indent=2)[:12000])
    else:
        print(f"unknown cmd {cmd}")
