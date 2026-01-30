# Undo/Redo Implementation Plan for Mason

## Overview

Add undo/redo functionality to the Mason block editor with:
- Session-only history (lost on page refresh)
- 20 levels of undo/redo
- Keyboard shortcuts (Ctrl+Z, Ctrl+Y, Ctrl+Shift+Z)
- Visual feedback (disabled buttons when stacks empty)

## Architecture

The implementation uses a **client-side state snapshot approach** in Alpine.js:
- `undoStack[]` and `redoStack[]` hold state snapshots
- State is captured BEFORE each operation
- Undo restores previous state, redo restores next state

## Files to Modify

| File | Changes |
|------|---------|
| `resources/js/mason.js` | Add history properties, methods, intercept state changes |
| `resources/views/components/controls.blade.php` | Wire up undo/redo buttons |
| `resources/views/iframe-preview-content.blade.php` | Forward keyboard shortcuts from iframe |

## Implementation Steps

### Step 1: Add History Properties to mason.js

Add these properties to the Alpine component return object (after line 24):

```javascript
// Undo/Redo state
undoStack: [],
redoStack: [],
maxHistorySize: 20,
isUndoRedoOperation: false,
```

### Step 2: Add History Management Methods

Add these methods to the component:

```javascript
captureState() {
    return JSON.parse(JSON.stringify(this.state || []));
},

pushToUndoStack(previousState) {
    if (this.isUndoRedoOperation) return;

    this.undoStack.push(previousState);
    if (this.undoStack.length > this.maxHistorySize) {
        this.undoStack.shift();
    }
    this.redoStack = [];
},

undo() {
    if (this.undoStack.length === 0) return;

    this.redoStack.push(this.captureState());
    if (this.redoStack.length > this.maxHistorySize) {
        this.redoStack.shift();
    }

    const previousState = this.undoStack.pop();
    this.isUndoRedoOperation = true;
    this.updateStateFromBlocks(previousState);
    this.isUndoRedoOperation = false;
},

redo() {
    if (this.redoStack.length === 0) return;

    this.undoStack.push(this.captureState());
    if (this.undoStack.length > this.maxHistorySize) {
        this.undoStack.shift();
    }

    const nextState = this.redoStack.pop();
    this.isUndoRedoOperation = true;
    this.updateStateFromBlocks(nextState);
    this.isUndoRedoOperation = false;
},

canUndo() {
    return this.undoStack.length > 0;
},

canRedo() {
    return this.redoStack.length > 0;
},

clearAllBlocks() {
    if (this.state && this.state.length > 0) {
        this.pushToUndoStack(this.captureState());
    }
    this.state = [];
    this.updateStateFromBlocks([]);
},
```

### Step 3: Modify State-Changing Operations

Add `this.pushToUndoStack(this.captureState());` as the first line in each handler:

**handleDeleteBlock** (line 217):
```javascript
handleDeleteBlock(index) {
    const blocks = this.getBlocksFromState();
    if (index >= 0 && index < blocks.length) {
        this.pushToUndoStack(this.captureState());  // ADD THIS
        const newBlocks = blocks.filter((_, i) => i !== index);
        this.updateStateFromBlocks(newBlocks);
    }
},
```

**handleUpdateBlock** (line 228):
```javascript
handleUpdateBlock(index, brick) {
    const blocks = this.getBlocksFromState();
    if (index >= 0 && index < blocks.length) {
        this.pushToUndoStack(this.captureState());  // ADD THIS
        const newBlocks = blocks.map((block, i) => i === index ? brick : block);
        this.updateStateFromBlocks(newBlocks);
    }
},
```

**handleMoveBlock** (line 241):
```javascript
handleMoveBlock(from, to) {
    const blocks = this.getBlocksFromState();
    if (from < 0 || from >= blocks.length) return;
    if (to < 0 || to > blocks.length) return;
    if (from === to) return;

    this.pushToUndoStack(this.captureState());  // ADD THIS
    // ... rest of existing logic
},
```

**handleInsertBlock** (line 184) - capture before async Livewire call:
```javascript
async handleInsertBlock(brickId, position) {
    this.isInsertingBrick = true;
    const preInsertState = this.captureState();  // ADD THIS

    try {
        await this.$wire.mountAction(/* existing args */);
        this.pushToUndoStack(preInsertState);  // ADD THIS (after successful insert)
    } finally {
        this.isInsertingBrick = false;
    }
},
```

**handleEditBlock** (line 198) - capture before async Livewire call:
```javascript
async handleEditBlock({ index, brickId, config }) {
    this.isUpdatingBrick = true;
    const preEditState = this.captureState();  // ADD THIS

    try {
        await this.$wire.mountAction(/* existing args */);
        this.pushToUndoStack(preEditState);  // ADD THIS (after successful edit)
    } finally {
        this.isUpdatingBrick = false;
    }
},
```

### Step 4: Add Keyboard Shortcuts

Add to the `init()` method (after line 112):

```javascript
// Keyboard shortcuts for undo/redo
const keyboardHandler = (event) => {
    const activeElement = document.activeElement;
    const isInputFocused = activeElement && (
        activeElement.tagName === 'INPUT' ||
        activeElement.tagName === 'TEXTAREA' ||
        activeElement.isContentEditable
    );
    if (isInputFocused) return;

    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const cmdOrCtrl = isMac ? event.metaKey : event.ctrlKey;

    if (cmdOrCtrl && !event.altKey) {
        if (event.key === 'z' || event.key === 'Z') {
            event.preventDefault();
            if (event.shiftKey) {
                this.redo();
            } else {
                this.undo();
            }
        } else if ((event.key === 'y' || event.key === 'Y') && !event.shiftKey) {
            event.preventDefault();
            this.redo();
        }
    }
};

window.addEventListener('keydown', keyboardHandler);
eventListeners.push(['keydown', keyboardHandler]);
```

Also add a case in the message handler switch statement:

```javascript
case 'keyboardShortcut':
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const cmdOrCtrl = isMac ? data.metaKey : data.ctrlKey;
    if (cmdOrCtrl && !data.altKey) {
        if (data.key === 'z' || data.key === 'Z') {
            data.shiftKey ? this.redo() : this.undo();
        } else if ((data.key === 'y' || data.key === 'Y') && !data.shiftKey) {
            this.redo();
        }
    }
    break;
```

### Step 5: Update Iframe to Forward Keyboard Events

In `iframe-preview-content.blade.php`, add to the script section:

```javascript
// Forward undo/redo shortcuts to parent
window.addEventListener('keydown', function(event) {
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const cmdOrCtrl = isMac ? event.metaKey : event.ctrlKey;

    if (cmdOrCtrl && !event.altKey) {
        if ((event.key === 'z' || event.key === 'Z') ||
            (event.key === 'y' || event.key === 'Y')) {
            window.parent.postMessage({
                type: 'keyboardShortcut',
                key: event.key,
                ctrlKey: event.ctrlKey,
                metaKey: event.metaKey,
                shiftKey: event.shiftKey,
                altKey: event.altKey
            }, '*');
            event.preventDefault();
        }
    }
});
```

### Step 6: Wire Up Buttons in controls.blade.php

Replace the placeholder undo/redo buttons (lines 75-92):

```blade
<x-filament::icon-button
    icon="heroicon-o-arrow-uturn-left"
    color="gray"
    x-on:click="undo()"
    size="sm"
    x-bind:disabled="!canUndo()"
    x-bind:class="{'opacity-50 cursor-not-allowed': !canUndo()}"
    title="Undo (Ctrl+Z)"
>
    Undo
</x-filament::icon-button>
<x-filament::icon-button
    icon="heroicon-o-arrow-uturn-right"
    color="gray"
    x-on:click="redo()"
    size="sm"
    x-bind:disabled="!canRedo()"
    x-bind:class="{'opacity-50 cursor-not-allowed': !canRedo()}"
    title="Redo (Ctrl+Y)"
>
    Redo
</x-filament::icon-button>
```

Also update the "Clear all blocks" button (line 4):
```blade
x-on:click="clearAllBlocks()"
```

## Testing Checklist

- [ ] Undo after delete block
- [ ] Undo after move block (up/down buttons)
- [ ] Undo after drag-and-drop reorder
- [ ] Undo after insert new brick
- [ ] Undo after edit brick content
- [ ] Undo after "Clear all blocks"
- [ ] Redo after undo
- [ ] Ctrl+Z / Cmd+Z triggers undo
- [ ] Ctrl+Y and Ctrl+Shift+Z trigger redo
- [ ] Buttons disabled when stacks are empty
- [ ] 21st action drops oldest history entry
- [ ] Redo stack clears on new action after undo
- [ ] Keyboard shortcuts work from iframe
- [ ] History persists across viewport changes
- [ ] History persists in fullscreen mode

## Verification

1. Run `npm run build` to compile the JavaScript
2. Test in browser with a Mason field
3. Perform various operations and verify undo/redo work
4. Run `composer test` to ensure no regressions
