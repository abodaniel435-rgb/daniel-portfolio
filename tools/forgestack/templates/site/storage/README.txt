Writable runtime directory.

The installer writes installed.lock here, cron jobs append automation.log,
and the framework may create short-lived cache files. Give the web-server user
write permission on cPanel (normally 0755 for the folder is enough; use 0775
when PHP-FPM runs as your account).

Do not serve this directory over HTTP.
