# FreeRADIUS Integration

RadBoX uses the standard FreeRADIUS SQL tables for authentication/accounting:

- `radcheck`
- `radreply`
- `radusergroup`
- `radgroupcheck`
- `radgroupreply`
- `radacct`
- `nas`

Application data stays in `rb_` tables. Do not add tenant columns to FreeRADIUS tables.

The important authorization rule is tenant validation from database data:

1. Find voucher by `User-Name` in `rb_vouchers`.
2. Find router by `NAS-IP-Address`, falling back to `Packet-Src-IP-Address`, in `rb_routers`.
3. Reject if tenant differs.
4. Reject if voucher is `expired` or `deleted`.
5. Reject if voucher has a specific `router_id` and the request comes from another router.
6. Reject when total uptime or validity is exhausted.

See `radbox-policy.conf` for an unlang-style policy snippet and `sqlcounter-example.conf` for total uptime notes.
