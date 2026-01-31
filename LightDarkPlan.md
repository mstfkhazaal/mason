# Light/Dark Mode Toggle Plan for Mason Field

## Overview

Add a light/dark mode toggle to the Mason field that controls the iframe's `dark` class on its `<html>` element. The toggle buttons will be placed in the controls area (both topbar and sidebar) alongside existing viewport and fullscreen controls.

## Architecture

The implementation follows Mason's existing patterns:
- **Controls Area**: Button UI in Blade component
- **Alpine.js**: State management and iframe communication
- **postMessage**: Communication between parent and iframe
- **Iframe Handler**: Receives message and toggles the `dark` class

---

## Files to Modify

| File | Purpose |
|------|---------|
| `resources/views/components/controls.blade.php` | Add light/dark toggle buttons |
| `resources/js/mason.js` | Add state property and toggle method |
| `resources/views/iframe-preview.blade.php` | Handle message and toggle class |
| `resources/css/plugin.css` | Optional: Active state styling for toggle |

---

## Implementation Steps

### Step 1: Add State Property in Alpine.js

**File:** `resources/js/mason.js`

Add a new reactive property to track the current mode:

```javascript
// In the data() section, add:
colorMode: 'light',  // 'light' or 'dark'
```

Consider localStorage persistence for user preference:

```javascript
colorMode: localStorage.getItem('mason-color-mode') || 'light',
```

### Step 2: Add Toggle Method in Alpine.js

**File:** `resources/js/mason.js`

Add a method to toggle the mode and communicate with the iframe:

```javascript
toggleColorMode(mode) {
    this.colorMode = mode;
    localStorage.setItem('mason-color-mode', mode);
    this.sendMessageToIframe({
        type: 'setColorMode',
        mode: mode
    });
}
```

### Step 3: Send Initial Color Mode on Iframe Load

**File:** `resources/js/mason.js`

In the `init()` method, after the iframe is ready, send the current color mode:

```javascript
// After existing setContent message in init()
this.sendMessageToIframe({
    type: 'setColorMode',
    mode: this.colorMode
});
```

### Step 4: Handle Message in Iframe

**File:** `resources/views/iframe-preview.blade.php`

Add a message handler in the iframe's script section:

```javascript
// In the message event listener switch statement
case 'setColorMode':
    if (event.data.mode === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    break;
```

### Step 5: Add Toggle Buttons to Controls

**File:** `resources/views/components/controls.blade.php`

Add light/dark mode toggle buttons after the viewport controls (before fullscreen):

```blade
{{-- Light Mode Button --}}
<x-filament::icon-button
    icon="heroicon-o-sun"
    x-on:click="toggleColorMode('light')"
    x-bind:class="{ 'active': colorMode === 'light' }"
    label="Light mode"
    size="sm"
/>

{{-- Dark Mode Button --}}
<x-filament::icon-button
    icon="heroicon-o-moon"
    x-on:click="toggleColorMode('dark')"
    x-bind:class="{ 'active': colorMode === 'dark' }"
    label="Dark mode"
    size="sm"
/>
```

**Alternative: Single Toggle Button**

If you prefer a single button that toggles between modes:

```blade
<x-filament::icon-button
    x-bind:icon="colorMode === 'dark' ? 'heroicon-o-sun' : 'heroicon-o-moon'"
    x-on:click="toggleColorMode(colorMode === 'dark' ? 'light' : 'dark')"
    x-bind:label="colorMode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    size="sm"
/>
```

---

## Optional Enhancements

### A. Follow System Preference

Add a third "system" option that respects `prefers-color-scheme`:

```javascript
// In data()
colorMode: localStorage.getItem('mason-color-mode') || 'system',

// In toggleColorMode or a new method
applyColorMode(mode) {
    let effectiveMode = mode;
    if (mode === 'system') {
        effectiveMode = window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }
    this.sendMessageToIframe({ type: 'setColorMode', mode: effectiveMode });
}
```

Add a system preference listener:

```javascript
// In init()
if (this.colorMode === 'system') {
    window.matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', (e) => {
            this.applyColorMode('system');
        });
}
```

### B. Server-Side Configuration

Add a method on the Mason field to set the default color mode:

**File:** `src/Mason.php`

```php
protected string $defaultColorMode = 'light';

public function defaultColorMode(string $mode): static
{
    $this->defaultColorMode = $mode;
    return $this;
}

public function getDefaultColorMode(): string
{
    return $this->defaultColorMode;
}
```

Pass to the Alpine component in the view.

### C. Follow Filament's Theme

If you want the iframe to automatically match Filament's current theme:

```javascript
// In init()
const filamentTheme = document.documentElement.classList.contains('dark')
    ? 'dark'
    : 'light';
this.colorMode = localStorage.getItem('mason-color-mode') || filamentTheme;
```

---

## CSS Considerations

### Active State for Buttons

The existing `.active` class in `plugin.css` should work for highlighting the active mode button. If needed, ensure it applies:

```css
.mason-controls button.active {
    --c-400: var(--primary-400);
    --c-500: var(--primary-500);
    --c-600: var(--primary-600);
}
```

### Iframe Dark Mode Styles

The iframe content will need corresponding dark mode styles. This depends on how bricks are styled. Common approach:

```css
/* In your brick stylesheets or preview.css */
.dark {
    color-scheme: dark;
}

.dark body {
    background-color: #1a1a1a;
    color: #e5e5e5;
}
```

---

## Testing Checklist

- [ ] Toggle button appears in controls (topbar on mobile, sidebar on desktop)
- [ ] Clicking light button sets `light` mode and removes `dark` class from iframe `<html>`
- [ ] Clicking dark button sets `dark` mode and adds `dark` class to iframe `<html>`
- [ ] Mode persists across page reloads (localStorage)
- [ ] Initial mode is applied when iframe loads
- [ ] Active state styling shows current mode
- [ ] Works correctly when entering/exiting fullscreen
- [ ] Works correctly when switching viewports (mobile/tablet/desktop)

---

## Summary

This implementation follows Mason's established patterns for:
1. **State management** via Alpine.js data properties
2. **UI controls** using Filament icon buttons with active state binding
3. **Iframe communication** via postMessage API
4. **Persistence** via localStorage

The minimal implementation requires changes to 4 files with approximately 30-40 lines of code total.
