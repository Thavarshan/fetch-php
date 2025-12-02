# Phase 3 Summary: Standardize Configuration and Options Vocabulary

**Date Completed:** 2025-12-02
**Status:** ✅ **COMPLETE** - All 446 tests passing

---

## Goals Achieved

✅ Analyzed current option key usage across codebase
✅ Created option normalization system
✅ Standardized `max_retries` → `retries` as canonical
✅ Maintained 100% backward compatibility
✅ Added comprehensive documentation
✅ Created 14 new tests (37 assertions)

---

## Changes Made

### 1. **RequestOptions Normalization** (`src/Fetch/Support/RequestOptions.php`)

**Added `normalizeOptionKeys()` method:**

```php
public static function normalizeOptionKeys(array $options): array
{
    // Normalize retry options: prefer 'retries' over 'max_retries'
    if (isset($options['max_retries']) && ! isset($options['retries'])) {
        $options['retries'] = $options['max_retries'];
        unset($options['max_retries']);
    }

    // If both are set, 'retries' takes precedence (remove duplicate)
    if (isset($options['retries']) && isset($options['max_retries'])) {
        unset($options['max_retries']);
    }

    return $options;
}
```

**Integrated into `merge()` method:**

```php
public static function merge(array ...$optionSets): array
{
    $result = [];

    foreach ($optionSets as $options) {
        // Normalize option keys to canonical names
        $options = self::normalizeOptionKeys($options);  // ✅ NEW

        // Normalize body options
        $options = self::normalizeBodyOptions($options);

        // ... rest of merge logic
    }

    return $result;
}
```

**Impact:**
- All options automatically normalized during merge
- No manual normalization required by calling code
- Transparent to users

### 2. **RequestContext Documentation** (`src/Fetch/Support/RequestContext.php`)

**Added comments to clarify normalization:**

```php
// Extract known properties
// Note: Options should be normalized via RequestOptions::normalizeOptionKeys before this point
// so 'max_retries' should already be converted to 'retries'
$uri = (string) ($options['uri'] ?? '');
$async = (bool) ($options['async'] ?? false);
$timeout = (int) ($options['timeout'] ?? 30);
$maxRetries = (int) ($options['retries'] ?? $options['max_retries'] ?? 1); // Fallback for safety
$retryDelay = (int) ($options['retry_delay'] ?? 100);
```

**Rationale:**
- RequestContext still accepts both keys as a safety fallback
- Primary normalization happens in RequestOptions::merge
- Defense in depth approach

### 3. **Comprehensive Testing** (`tests/Unit/RequestOptionsTest.php`)

**Created 14 new tests:**

1. ✅ `test_normalize_option_keys_converts_max_retries_to_retries`
2. ✅ `test_normalize_option_keys_keeps_retries_when_present`
3. ✅ `test_normalize_option_keys_retries_takes_precedence_over_max_retries`
4. ✅ `test_normalize_option_keys_preserves_other_options`
5. ✅ `test_merge_normalizes_option_keys`
6. ✅ `test_merge_with_both_retries_and_max_retries_prefers_retries`
7. ✅ `test_backward_compatibility_max_retries_still_works`
8. ✅ `test_normalize_body_options`
9. ✅ `test_merge_deep_merges_headers`
10. ✅ `test_merge_deep_merges_query_parameters`
11. ✅ `test_validate_accepts_valid_options`
12. ✅ `test_validate_throws_on_negative_timeout`
13. ✅ `test_validate_throws_on_negative_retries`
14. ✅ `test_validate_throws_on_invalid_base_uri`

**Coverage:**
- Normalization with legacy keys
- Normalization with canonical keys
- Precedence when both keys present
- Backward compatibility
- Integration with merge()
- Edge cases

### 4. **Documentation** (`docs/internal/CANONICAL_OPTIONS.md`)

**Created comprehensive documentation covering:**

- ✅ Overview of canonical vs legacy options
- ✅ Complete option reference table
- ✅ Precedence rules
- ✅ Migration guide (optional)
- ✅ Implementation details
- ✅ Best practices
- ✅ Future considerations

---

## Canonical Option Names

### Established Canonical Form

| Canonical | Legacy | Reason |
|-----------|--------|--------|
| `retries` | `max_retries` | More concise, matches method name `retry()` |

### Rationale for "retries" over "max_retries"

1. **Brevity:** Shorter, easier to type
2. **Consistency:** Matches public method name `$handler->retry()`
3. **Common usage:** Most examples already use `retries`
4. **No ambiguity:** Context makes it clear it's the maximum

---

## Backward Compatibility

### ✅ 100% Backward Compatible

**Legacy code continues to work:**

```php
// Old code (still works!)
$handler->sendRequest('GET', '/api/data', [
    'max_retries' => 5,
]);

// Internally normalized to:
// ['retries' => 5]
```

**No deprecation warnings:**
- Legacy keys are not deprecated
- No runtime warnings
- No breaking changes
- Silent normalization

**Smooth upgrade path:**
- Users can migrate at their own pace
- Both keys work simultaneously
- Canonical key takes precedence if both present

---

## Test Results

### Before Phase 3
- **Tests:** 432
- **Assertions:** 1266

### After Phase 3
- **Tests:** 446 (+14)
- **Assertions:** 1303 (+37)
- **Status:** ✅ **100% passing**

### Test Categories

| Category | Tests | Status |
|----------|-------|--------|
| Option normalization | 7 | ✅ Pass |
| Merge behavior | 3 | ✅ Pass |
| Validation | 3 | ✅ Pass |
| Backward compat | 1 | ✅ Pass |
| **Total** | **14** | ✅ **Pass** |

---

## Implementation Approach

### Design Principles

1. **Non-breaking:** All changes are additive
2. **Transparent:** Normalization happens automatically
3. **Defensive:** Multiple layers of fallback
4. **Well-tested:** Comprehensive test coverage
5. **Documented:** Clear documentation for users

### Normalization Flow

```
User Code
   ↓
   [ withOptions() / sendRequest() ]
   ↓
RequestOptions::merge()
   ↓
   [ normalizeOptionKeys() ]  ← Converts max_retries → retries
   ↓
   [ normalizeBodyOptions() ]
   ↓
Merged Options
   ↓
RequestContext::fromOptions()
   ↓
   [ Fallback: retries ?? max_retries ?? 1 ]  ← Safety net
   ↓
RequestContext (canonical keys only)
```

### Why This Approach?

**Centralized normalization in `RequestOptions::merge()`:**
- ✅ Single source of truth
- ✅ Happens automatically during option processing
- ✅ No manual normalization calls needed
- ✅ Easy to extend for future normalizations

**Fallback in `RequestContext`:**
- ✅ Defense in depth
- ✅ Handles edge cases
- ✅ Safety net for direct construction

---

## Usage Examples

### New Code (Canonical)

```php
// ✅ Preferred: Use canonical names
$handler->sendRequest('GET', '/api/users', [
    'retries' => 3,
    'retry_delay' => 200,
    'timeout' => 30,
]);
```

### Legacy Code (Still Works)

```php
// ⚠️ Legacy: Still works, automatically normalized
$handler->sendRequest('GET', '/api/users', [
    'max_retries' => 3,
    'retry_delay' => 200,
    'timeout' => 30,
]);
```

### Mixed Keys (Canonical Wins)

```php
// Canonical key takes precedence
$handler->sendRequest('GET', '/api/users', [
    'retries' => 10,       // ✅ Used
    'max_retries' => 5,    // ❌ Ignored
]);
```

### Merging with Normalization

```php
$defaults = ['max_retries' => 3];
$override = ['timeout' => 60];

$merged = RequestOptions::merge($defaults, $override);
// Result: ['retries' => 3, 'timeout' => 60]
// Note: 'max_retries' normalized to 'retries'
```

---

## Future Extensions

### Adding More Normalizations

The `normalizeOptionKeys()` method can easily be extended:

```php
public static function normalizeOptionKeys(array $options): array
{
    // Current: retries
    if (isset($options['max_retries']) && ! isset($options['retries'])) {
        $options['retries'] = $options['max_retries'];
        unset($options['max_retries']);
    }

    // Future: Add more normalizations here
    // Example: connect_timeout → connection_timeout
    // Example: ssl_verify → verify

    return $options;
}
```

---

## Key Learnings

### ✅ What Worked Well

1. **Transparent normalization** - Users don't need to think about it
2. **Backward compatibility first** - No breaking changes
3. **Comprehensive testing** - High confidence in changes
4. **Clear documentation** - Users know what to expect
5. **Incremental approach** - Started with one option pair

### 🎯 Design Decisions

1. **"retries" as canonical** - Shorter, matches method name
2. **Normalize in merge()** - Single point of normalization
3. **Fallback in RequestContext** - Defense in depth
4. **No deprecation warnings** - Smooth user experience
5. **Comprehensive docs** - Clear migration path (optional)

### 💡 Insights

1. **Users value stability** - Backward compat is critical
2. **Silent normalization is powerful** - No user action required
3. **Good tests enable refactoring** - 446 tests caught all issues
4. **Documentation matters** - Users need clear guidance
5. **Small changes add up** - Incremental improvements work

---

## Phase 3 Completion Checklist

- [x] Analyzed current option key usage
- [x] Identified canonical vs legacy keys
- [x] Created normalization system
- [x] Integrated normalization into merge flow
- [x] Added safety fallbacks
- [x] Created comprehensive tests (14 tests, 37 assertions)
- [x] Documented canonical option names
- [x] Verified backward compatibility
- [x] All 446 tests passing
- [x] No breaking changes

---

## What's Next: Phase 4

**Goal:** Decouple logging/debug/profiling from handler state

**Tasks:**
1. Move `lastDebugInfo` off handler instance
2. Make debug info per-request
3. Return debug info as values instead of mutations
4. Add tests for debug info isolation in concurrent requests

---

## Summary

Phase 3 successfully standardized configuration vocabulary while maintaining perfect backward compatibility:

- ✅ **Canonical option names** - Clear, consistent vocabulary
- ✅ **Automatic normalization** - Transparent to users
- ✅ **Backward compatible** - Legacy keys still work
- ✅ **Well-tested** - 14 new tests, 37 assertions
- ✅ **Well-documented** - Comprehensive guide
- ✅ **No breaking changes** - Existing code unaffected

**Configuration is now consistent, predictable, and future-proof!** 🎯
