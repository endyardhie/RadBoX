# SQL Query Notes

Tenant validation must not rely on username prefixes alone.

Core check:

```sql
SELECT COUNT(*)
FROM rb_vouchers v
JOIN rb_routers r ON r.tenant_id = v.tenant_id
WHERE v.username = :username
  AND r.nas_ip_address = :nas_ip
  AND v.status NOT IN ('expired','deleted')
  AND (v.router_id IS NULL OR v.router_id = r.id);
```

Fallback NAS IP value:

1. `NAS-IP-Address`
2. `Packet-Src-IP-Address`

Expired vouchers should have `radcheck`, `radreply`, and `radusergroup` access rows removed by RadBoX sync.
