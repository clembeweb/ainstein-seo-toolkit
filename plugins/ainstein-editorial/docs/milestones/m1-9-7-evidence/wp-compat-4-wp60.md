# WP Compat Test — Install #4 (wp60)

**Data**: 2026-05-20
**WP version**: 6.0
**PHP version**: 8.1.10
**Plugin AE version**: 0.1.0
**Stack**: WP 6.0 + PHP 8.1 — backward compat (floor dichiarato 6.0/PHP8.0; PHP 8.0 non disponibile localmente)
**Plugin attivi**: `ainstein-editorial `

## Esito complessivo
⚠️ PASS con note

## Smoke test results
| Step | Esito | Note |
|------|-------|------|
| Plugin AE + stack attivi | ✅ | tutti attivi |
| Bootstrap WP completo (no fatal) | ✅ | classe autoloadata, version 0.1.0 |
| admin_init + admin_menu fire | ✅ | hook errors: [1024] Function wp_add_privacy_policy_content was called <strong>incorrectly</strong>. The suggested privacy policy content should be added only in wp-admin by using the <code>admin_init</code> (or later) action. Please see <a href="https://wordpress.org/support/article/debugging-in-wordpress/">Debugging in WordPress</a> for more information. (This message was added in version 4.9.7.) |
| save_post (crea/elimina draft) | ✅ | nessuna collisione save_post |
| debug.log pulito (plugin) | ⚠️ | 11 righe WP/core (deprecations + auto-update info), non plugin-attribuibili |

## Probe headless (output)
```
aied_version=0.1.0
aied_activated=yes
class_plugin=yes
const_version=0.1.1
admin_hooks=ok
admin_hook_errors=[1024] Function wp_add_privacy_policy_content was called <strong>incorrectly</strong>. The suggested privacy policy content should be added only in wp-admin by using the <code>admin_init</code> (or later) action. Please see <a href="https://wordpress.org/support/article/debugging-in-wordpress/">Debugging in WordPress</a> for more information. (This message was added in version 4.9.7.)
```

## debug.log estratto (max 25 righe)
```
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Cookie_Jar::offsetExists($key) should either be compatible with ArrayAccess::offsetExists(mixed $offset): bool, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Cookie\Jar.php on line 63
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Cookie_Jar::offsetGet($key) should either be compatible with ArrayAccess::offsetGet(mixed $offset): mixed, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Cookie\Jar.php on line 73
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Cookie_Jar::offsetSet($key, $value) should either be compatible with ArrayAccess::offsetSet(mixed $offset, mixed $value): void, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Cookie\Jar.php on line 89
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Cookie_Jar::offsetUnset($key) should either be compatible with ArrayAccess::offsetUnset(mixed $offset): void, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Cookie\Jar.php on line 102
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Cookie_Jar::getIterator() should either be compatible with IteratorAggregate::getIterator(): Traversable, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Cookie\Jar.php on line 111
[20-May-2026 09:22:40 UTC] PHP Deprecated:  http_build_query(): Passing null to parameter #2 ($numeric_prefix) of type string is deprecated in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Transport\cURL.php on line 345
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Utility_CaseInsensitiveDictionary::offsetExists($key) should either be compatible with ArrayAccess::offsetExists(mixed $offset): bool, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Utility\CaseInsensitiveDictionary.php on line 40
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Utility_CaseInsensitiveDictionary::offsetGet($key) should either be compatible with ArrayAccess::offsetGet(mixed $offset): mixed, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Utility\CaseInsensitiveDictionary.php on line 51
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Utility_CaseInsensitiveDictionary::offsetSet($key, $value) should either be compatible with ArrayAccess::offsetSet(mixed $offset, mixed $value): void, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Utility\CaseInsensitiveDictionary.php on line 68
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Utility_CaseInsensitiveDictionary::offsetUnset($key) should either be compatible with ArrayAccess::offsetUnset(mixed $offset): void, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Utility\CaseInsensitiveDictionary.php on line 82
[20-May-2026 09:22:40 UTC] PHP Deprecated:  Return type of Requests_Utility_CaseInsensitiveDictionary::getIterator() should either be compatible with IteratorAggregate::getIterator(): Traversable, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in C:\laragon\www\wp-compat-4-wp60\wp-includes\Requests\Utility\CaseInsensitiveDictionary.php on line 91
```

## Conclusione
⚠️ Procedibile: note sono deprecation WP/core non plugin-attribuibili.

---
*Generato da `tests/wp-compat/smoke.sh` (M1.9.7). Smoke headless via wp-cli: rileva fatal/collisioni hook a bootstrap, admin_init/admin_menu e save_post. UI render verificata via browser spot-check separato (vedi screenshot evidence se presenti).*
