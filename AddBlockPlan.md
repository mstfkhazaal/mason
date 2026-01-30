# Mobile Block Insertion Feature Plan

## Overview
Add a new button to Mason block controls that opens a modal for selecting and inserting bricks on mobile devices, since the sidebar is hidden on smaller screens.

## Current Architecture
- **Sidebar** (`sidebar.blade.php`): Hidden on mobile via `hidden md:flex` CSS classes
- **Block controls** (`iframe-preview-content.blade.php` lines 31-115): Move up/down, edit, delete buttons inside iframe
- **Insert mechanism** (`mason.js` line 184): `handleInsertBlock(brickId, position)` method
- **Communication**: postMessage protocol between iframe and parent

## Implementation Approach

### 1. Add "Add Block" Button to Block Controls
**File**: `resources/views/iframe-preview-content.blade.php`

Add a new button after the move down button with `data-action="add"`. This button will send a postMessage to the parent requesting to open the brick picker modal.

```html
<button class="mason-block-btn" title="Add Block" data-action="add">
  <svg><!-- Plus icon --></svg>
</button>
```

### 2. Handle "Add Block" Action in Iframe Script
**File**: `resources/views/iframe-preview-content.blade.php` (script section around line 320)

When `data-action="add"` is clicked, send a postMessage to parent:
```javascript
} else if (actionType === 'add') {
    window.parent.postMessage({
        type: 'openBrickPicker',
        blockIndex: index,
    }, '*')
}
```

### 3. Create Mobile Brick Picker Modal Component
**File**: `resources/views/components/brick-picker-modal.blade.php` (new file)

A modal overlay with:
- List of available bricks (reuse brick rendering logic from sidebar)
- Position selector: "Insert above" / "Insert below" toggle
- Search functionality (reuse from sidebar)
- Close button

### 4. Add Modal to Main Mason Template
**File**: `resources/views/mason.blade.php`

Include the brick picker modal component after the sidebar. It should be conditionally visible based on Alpine state.

### 5. Update Alpine Component to Handle Modal
**File**: `resources/js/mason.js`

Add new state and methods:
```javascript
// New state
brickPickerOpen: false,
brickPickerBlockIndex: null,
brickPickerPosition: 'below', // 'above' or 'below'

// New message handler case
case 'openBrickPicker':
    this.openBrickPicker(data.blockIndex)
    break

// New methods
openBrickPicker(blockIndex) {
    this.brickPickerBlockIndex = blockIndex
    this.brickPickerOpen = true
}

closeBrickPicker() {
    this.brickPickerOpen = false
    this.brickPickerBlockIndex = null
    this.brickPickerPosition = 'below'
}

insertFromPicker(brickId) {
    const position = this.brickPickerPosition === 'above'
        ? this.brickPickerBlockIndex
        : this.brickPickerBlockIndex + 1
    this.handleInsertBlock(brickId, position)
    this.closeBrickPicker()
}
```

### 6. Add Translations
**Files**: `resources/lang/en/mason.php`, `resources/lang/ar/mason.php`

Add new translation keys:
```php
'preview' => [
    // existing...
    'add' => 'Add Block',
],
'brick_picker' => [
    'title' => 'Add Block',
    'insert_above' => 'Insert above',
    'insert_below' => 'Insert below',
],
```

### 7. Add Modal Styling
**File**: `resources/css/plugin.css`

Add styles for the brick picker modal:
```css
.mason-brick-picker-modal {
    /* Full-screen overlay on mobile, centered modal on larger screens */
    /* Use similar styling to Filament modals for consistency */
}
```

## Files to Modify

| File | Changes |
|------|---------|
| `resources/views/iframe-preview-content.blade.php` | Add "Add Block" button, handle click action |
| `resources/views/mason.blade.php` | Include brick picker modal component |
| `resources/views/components/brick-picker-modal.blade.php` | **New file** - Modal with brick list and position selector |
| `resources/js/mason.js` | Add state, message handler, and methods for modal |
| `resources/css/plugin.css` | Modal styling |
| `resources/lang/en/mason.php` | Add translation keys |
| `resources/lang/ar/mason.php` | Add Arabic translation keys |

## UI/UX Considerations

1. **Button visibility**: The "Add Block" button will always be visible in block controls on all screen sizes - useful for quick insertion without dragging from sidebar
2. **Position default**: Default to "Insert below" since that's the more common use case
3. **Modal accessibility**: Ensure modal can be closed with Escape key and clicking outside
4. **Loading state**: Show loading indicator while brick is being inserted
5. **Mobile-first**: Modal should be full-screen on mobile, drawer/modal on larger screens

## Testing

1. On mobile viewport, select a block and verify "Add Block" button appears
2. Click "Add Block" and verify modal opens with brick list
3. Select a brick and verify it's inserted at the correct position (above/below)
4. Test search functionality in the modal
5. Test closing modal with X button, Escape key, and clicking outside
6. Verify existing sidebar drag-and-drop still works on desktop
