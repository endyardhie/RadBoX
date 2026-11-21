# NAS / Clients Notes

RadBoX syncs routers into the FreeRADIUS `nas` table:

- `nas.nasname` = `rb_routers.nas_ip_address`
- `nas.shortname` = router name
- `nas.type` = `mikrotik`
- `nas.secret` = router RADIUS secret
- `nas.description` = tenant/router label

Enable SQL clients in FreeRADIUS if you want `nas` rows loaded dynamically, or mirror them into `clients.conf`.

MikroTik should point RADIUS authentication to the VPS IP on UDP `1812` and accounting to UDP `1813`.
