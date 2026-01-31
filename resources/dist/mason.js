function B({
    key: d,
    state: m,
    statePath: y,
    disabled: h,
    dblClickToEdit: p,
    bricks: S = [],
    previewLayout: g = null,
    defaultColorMode: k = 'light',
    hasColorModeToggle: u = !1,
}) {
    let n = null,
        l = [],
        f = !1,
        c = { x: 0, y: 0 }
    return {
        state: m,
        statePath: y,
        fullscreen: !1,
        viewport: 'desktop',
        sidebarOpen: !0,
        colorMode: (u && localStorage.getItem('mason-color-mode')) || k,
        isUpdatingBrick: !1,
        isInsertingBrick: !1,
        previewUrl: null,
        previewLayout: g,
        brickPickerOpen: !1,
        brickPickerBlockIndex: null,
        brickPickerPosition: 'below',
        undoStack: [],
        redoStack: [],
        maxHistorySize: 20,
        isUndoRedoOperation: !1,
        async init() {
            this.$nextTick(() => {
                ;((n = this.$refs.previewIframe),
                    n &&
                        (this.updatePreview(),
                        n.addEventListener('load', () => {
                            ;(this.restoreScrollPosition(),
                                this.sendMessageToIframe({
                                    type: 'setContent',
                                    blocks: this.getBlocksFromState(),
                                    dblClickToEdit: p,
                                    disabled: h,
                                }),
                                this.sendMessageToIframe({
                                    type: 'setColorMode',
                                    mode: this.colorMode,
                                }),
                                setTimeout(() => {
                                    this.sendMessageToIframe({
                                        type: 'updateMoveButtons',
                                    })
                                }, 100))
                        })))
            })
            let t = (e) => {
                let { type: o, ...i } = e.data
                switch (o) {
                    case 'ready':
                        this.sendMessageToIframe({
                            type: 'setContent',
                            blocks: this.getBlocksFromState(),
                        })
                        break
                    case 'editBlock':
                        this.handleEditBlock(i)
                        break
                    case 'deleteBlockRequest':
                        this.handleDeleteBlock(i.index)
                        break
                    case 'insertBlockRequest':
                        this.handleInsertBlock(i.brickId, i.position)
                        break
                    case 'updateBlockRequest':
                        this.handleUpdateBlock(i.index, i.brick)
                        break
                    case 'moveBlockRequest':
                        this.handleMoveBlock(i.from, i.to)
                        break
                    case 'openBrickPicker':
                        this.openBrickPicker(i.blockIndex)
                        break
                    case 'keyboardShortcut':
                        ;(navigator.platform.toUpperCase().indexOf('MAC') >= 0
                            ? i.metaKey
                            : i.ctrlKey) &&
                            !i.altKey &&
                            (i.key === 'z' || i.key === 'Z'
                                ? i.shiftKey
                                    ? this.redo()
                                    : this.undo()
                                : (i.key === 'y' || i.key === 'Y') &&
                                  !i.shiftKey &&
                                  this.redo())
                        break
                }
            }
            ;(window.addEventListener('message', t), l.push(['message', t]))
            let s = (e) => {
                let o = document.activeElement
                if (
                    o &&
                    (o.tagName === 'INPUT' ||
                        o.tagName === 'TEXTAREA' ||
                        o.isContentEditable)
                )
                    return
                ;(navigator.platform.toUpperCase().indexOf('MAC') >= 0
                    ? e.metaKey
                    : e.ctrlKey) &&
                    !e.altKey &&
                    (e.key === 'z' || e.key === 'Z'
                        ? (e.preventDefault(),
                          e.shiftKey ? this.redo() : this.undo())
                        : (e.key === 'y' || e.key === 'Y') &&
                          !e.shiftKey &&
                          (e.preventDefault(), this.redo()))
            }
            ;(window.addEventListener('keydown', s),
                l.push(['keydown', s]),
                this.$watch('state', () => {
                    f ||
                        (this.updateIframeContent(),
                        setTimeout(() => {
                            if (n && n.contentWindow)
                                try {
                                    n.contentWindow.postMessage(
                                        { type: 'updateMoveButtons' },
                                        '*',
                                    )
                                } catch {}
                        }, 200))
                }),
                this.setupDragAndDrop())
        },
        getBlocksFromState() {
            if (!this.state) return []
            let t = []
            try {
                t = JSON.parse(JSON.stringify(this.state))
            } catch {
                Array.isArray(this.state) && (t = Array.from(this.state))
            }
            return Array.isArray(t)
                ? t.filter((s) => s && s.type === 'masonBrick')
                : []
        },
        sendMessageToIframe(t) {
            n && n.contentWindow && n.contentWindow.postMessage(t, '*')
        },
        updateIframeContent() {
            ;(this.updatePreview(),
                this.sendMessageToIframe({
                    type: 'setConfig',
                    dblClickToEdit: p,
                    disabled: h,
                }))
        },
        setupDragAndDrop() {
            let t = this.$el.querySelector('.mason-editor-wrapper')
            t &&
                (t.addEventListener('dragover', (s) => {
                    s.preventDefault()
                }),
                t.addEventListener('drop', (s) => {
                    s.preventDefault()
                    let e = s.dataTransfer.getData('brick')
                    if (e) {
                        let i = this.getBlocksFromState().length
                        this.handleInsertBlock(e, i)
                    }
                }))
        },
        async handleInsertBlock(t, s) {
            this.isInsertingBrick = !0
            let e = this.captureState()
            try {
                ;(await this.$wire.mountAction(
                    'handleBrick',
                    { id: t, dragPosition: s, mode: 'insert' },
                    { schemaComponent: d },
                ),
                    this.pushToUndoStack(e))
            } finally {
                this.isInsertingBrick = !1
            }
        },
        async handleEditBlock({ index: t, brickId: s, config: e }) {
            this.isUpdatingBrick = !0
            let o = this.captureState()
            try {
                ;(await this.$wire.mountAction(
                    'handleBrick',
                    { id: s, config: e, mode: 'edit', blockIndex: t },
                    { schemaComponent: d },
                ),
                    this.pushToUndoStack(o))
            } finally {
                this.isUpdatingBrick = !1
            }
        },
        handleDeleteBlock(t) {
            let s = this.getBlocksFromState()
            if (t >= 0 && t < s.length) {
                this.pushToUndoStack(this.captureState())
                let e = s.filter((o, i) => i !== t)
                this.updateStateFromBlocks(e)
            }
        },
        handleUpdateBlock(t, s) {
            let e = this.getBlocksFromState()
            if (t >= 0 && t < e.length) {
                this.pushToUndoStack(this.captureState())
                let o = e.map((i, r) => (r === t ? s : i))
                this.updateStateFromBlocks(o)
            }
        },
        handleMoveBlock(t, s) {
            let e = this.getBlocksFromState()
            if (t < 0 || t >= e.length || s < 0 || s > e.length || t === s)
                return
            this.pushToUndoStack(this.captureState())
            let o = e.slice(),
                i = o[t]
            o.splice(t, 1)
            let r
            ;(s === e.length
                ? (r = o.length)
                : s > t
                  ? s === t + 1
                      ? (r = s)
                      : (r = s - 1)
                  : (r = s),
                (r = Math.max(0, Math.min(r, o.length))),
                o.splice(r, 0, i),
                this.updateStateFromBlocks(o),
                setTimeout(() => {
                    this.sendMessageToIframe({ type: 'updateMoveButtons' })
                }, 150))
        },
        updateStateFromBlocks(t) {
            let s = t
            try {
                s = JSON.parse(JSON.stringify(t))
            } catch {
                Array.isArray(t) &&
                    (s = t.map((i) => {
                        try {
                            return JSON.parse(JSON.stringify(i))
                        } catch {
                            return i
                        }
                    }))
            }
            this.state = s
            let e = this.statePath
            this.$wire.set(e, s, !1)
        },
        toggleFullscreen() {
            ;((this.fullscreen = !this.fullscreen),
                this.fullscreen || (this.viewport = 'desktop'))
        },
        toggleViewport(t) {
            if (this.viewport === t) {
                this.viewport = 'desktop'
                return
            }
            this.viewport = t
        },
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen
        },
        toggleColorMode() {
            ;((this.colorMode = this.colorMode === 'dark' ? 'light' : 'dark'),
                u && localStorage.setItem('mason-color-mode', this.colorMode),
                this.sendMessageToIframe({
                    type: 'setColorMode',
                    mode: this.colorMode,
                }))
        },
        saveScrollPosition() {
            if (n && n.contentWindow)
                try {
                    c = {
                        x:
                            n.contentWindow.scrollX ||
                            n.contentWindow.pageXOffset ||
                            0,
                        y:
                            n.contentWindow.scrollY ||
                            n.contentWindow.pageYOffset ||
                            0,
                    }
                } catch {
                    c = { x: 0, y: 0 }
                }
        },
        restoreScrollPosition() {
            if (n && n.contentWindow && (c.x > 0 || c.y > 0))
                try {
                    let t = () => {
                        if (n && n.contentWindow) {
                            let s =
                                n.contentDocument || n.contentWindow.document
                            s && s.readyState === 'complete'
                                ? n.contentWindow.scrollTo(c.x, c.y)
                                : setTimeout(t, 50)
                        }
                    }
                    ;(setTimeout(t, 0), setTimeout(t, 50), setTimeout(t, 100))
                } catch {}
        },
        updatePreview() {
            this.saveScrollPosition()
            let t = this.getBlocksFromState(),
                s = t
            try {
                s = JSON.parse(JSON.stringify(t))
            } catch {
                s = t
            }
            let e = document.createElement('form')
            ;((e.method = 'POST'),
                (e.action = '/mason/preview'),
                (e.target = 'mason-preview-iframe'),
                (e.style.display = 'none'))
            let o = document.createElement('input')
            ;((o.type = 'hidden'),
                (o.name = 'blocks'),
                (o.value = JSON.stringify(s)),
                e.appendChild(o))
            let i = document.createElement('input')
            if (
                ((i.type = 'hidden'),
                (i.name = 'bricks'),
                (i.value = JSON.stringify(S)),
                e.appendChild(i),
                this.previewLayout)
            ) {
                let a = document.createElement('input')
                ;((a.type = 'hidden'),
                    (a.name = 'layout'),
                    (a.value = this.previewLayout),
                    e.appendChild(a))
            }
            let r = document.createElement('input')
            ;((r.type = 'hidden'),
                (r.name = '_token'),
                (r.value =
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || ''),
                e.appendChild(r),
                document.body.appendChild(e),
                e.submit(),
                setTimeout(() => {
                    e.parentNode && e.parentNode.removeChild(e)
                }, 100))
        },
        deselectAllBlocks() {
            this.isUpdatingBrick ||
                this.sendMessageToIframe({ type: 'deselectAllBlocks' })
        },
        openBrickPicker(t) {
            ;((this.brickPickerBlockIndex = t), (this.brickPickerOpen = !0))
        },
        closeBrickPicker() {
            ;((this.brickPickerOpen = !1),
                (this.brickPickerBlockIndex = null),
                (this.brickPickerPosition = 'below'))
        },
        insertFromPicker(t) {
            let s =
                this.brickPickerPosition === 'above'
                    ? this.brickPickerBlockIndex
                    : this.brickPickerBlockIndex + 1
            ;(this.handleInsertBlock(t, s), this.closeBrickPicker())
        },
        captureState() {
            return JSON.parse(JSON.stringify(this.state || []))
        },
        pushToUndoStack(t) {
            this.isUndoRedoOperation ||
                (this.undoStack.push(t),
                this.undoStack.length > this.maxHistorySize &&
                    this.undoStack.shift(),
                (this.redoStack = []))
        },
        undo() {
            if (this.undoStack.length === 0) return
            ;(this.redoStack.push(this.captureState()),
                this.redoStack.length > this.maxHistorySize &&
                    this.redoStack.shift())
            let t = this.undoStack.pop()
            ;((this.isUndoRedoOperation = !0),
                this.updateStateFromBlocks(t),
                (this.isUndoRedoOperation = !1))
        },
        redo() {
            if (this.redoStack.length === 0) return
            ;(this.undoStack.push(this.captureState()),
                this.undoStack.length > this.maxHistorySize &&
                    this.undoStack.shift())
            let t = this.redoStack.pop()
            ;((this.isUndoRedoOperation = !0),
                this.updateStateFromBlocks(t),
                (this.isUndoRedoOperation = !1))
        },
        canUndo() {
            return this.undoStack.length > 0
        },
        canRedo() {
            return this.redoStack.length > 0
        },
        clearAllBlocks() {
            ;(this.state &&
                this.state.length > 0 &&
                this.pushToUndoStack(this.captureState()),
                (this.state = []),
                this.updateStateFromBlocks([]))
        },
        destroy() {
            ;((f = !0),
                l.forEach(([t, s]) => {
                    window.removeEventListener(t, s)
                }),
                (l = []),
                (n = null))
        },
    }
}
export { B as default }
