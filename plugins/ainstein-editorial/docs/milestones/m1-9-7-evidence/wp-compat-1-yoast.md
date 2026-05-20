# WP Compat Test — Install #1 (yoast)

**Data**: 2026-05-20
**WP version**: 6.9.4
**PHP version**: 8.3.22
**Plugin AE version**: 0.1.0
**Stack**: Yoast SEO — collisione meta-tag SEO #1
**Plugin attivi**: `ainstein-editorial wordpress-seo `

## Esito complessivo
✅ PASS

## Smoke test results
| Step | Esito | Note |
|------|-------|------|
| Plugin AE + stack attivi | ✅ | tutti attivi |
| Bootstrap WP completo (no fatal) | ✅ | classe autoloadata, version 0.1.0 |
| admin_init + admin_menu fire | ✅ | hook errors: [1024] Function wp_add_privacy_policy_content was called <strong>incorrectly</strong>. The suggested privacy policy content should be added only in wp-admin by using the <code>admin_init</code> (or later) action. Please see <a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/">Debugging in WordPress</a> for more information. (This message was added in version 4.9.7.) |
| save_post (crea/elimina draft) | ✅ | nessuna collisione save_post |
| debug.log pulito (plugin) | ✅ | debug.log vuoto |

## Probe headless (output)
```
aied_version=0.1.0
aied_activated=yes
class_plugin=yes
const_version=0.1.0
admin_hooks=ok
admin_hook_errors=[1024] Function wp_add_privacy_policy_content was called <strong>incorrectly</strong>. The suggested privacy policy content should be added only in wp-admin by using the <code>admin_init</code> (or later) action. Please see <a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/">Debugging in WordPress</a> for more information. (This message was added in version 4.9.7.)
```

## debug.log estratto (max 25 righe)
```
(vuoto)
```

## Conclusione
✅ Procedibile come è — nessuna collisione con lo stack testato.

---
*Generato da `tests/wp-compat/smoke.sh` (M1.9.7). Smoke headless via wp-cli: rileva fatal/collisioni hook a bootstrap, admin_init/admin_menu e save_post. UI render verificata via browser spot-check separato (vedi screenshot evidence se presenti).*
