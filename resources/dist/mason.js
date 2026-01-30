function g({
    key: d,
    state: m,
    statePath: f,
    disabled: h,
    dblClickToEdit: p,
    bricks: k = [],
    previewLayout: y = null,
}) {
    let i = null,
        c = [],
        u = !1,
        r = { x: 0, y: 0 }
    return {
        state: m,
        statePath: f,
        fullscreen: !1,
        viewport: 'desktop',
        sidebarOpen: !0,
        isUpdatingBrick: !1,
        isInsertingBrick: !1,
        previewUrl: null,
        previewLayout: y,
        async init() {
            this.$nextTick(() => {
                ;((i = this.$refs.previewIframe),
                    i &&
                        (this.updatePreview(),
                        i.addEventListener('load', () => {
                            ;(this.restoreScrollPosition(),
                                this.sendMessageToIframe({
                                    type: 'setContent',
                                    blocks: this.getBlocksFromState(),
                                    dblClickToEdit: p,
                                    disabled: h,
                                }),
                                setTimeout(() => {
                                    this.sendMessageToIframe({
                                        type: 'updateMoveButtons',
                                    })
                                }, 100))
                        })))
            })
            let e = (t) => {
                let { type: s, ...n } = t.data
                switch (s) {
                    case 'ready':
                        this.sendMessageToIframe({
                            type: 'setContent',
                            blocks: this.getBlocksFromState(),
                        })
                        break
                    case 'editBlock':
                        this.handleEditBlock(n)
                        break
                    case 'deleteBlockRequest':
                        this.handleDeleteBlock(n.index)
                        break
                    case 'insertBlockRequest':
                        this.handleInsertBlock(n.brickId, n.position)
                        break
                    case 'updateBlockRequest':
                        this.handleUpdateBlock(n.index, n.brick)
                        break
                    case 'moveBlockRequest':
                        this.handleMoveBlock(n.from, n.to)
                        break
                }
            }
            ;(window.addEventListener('message', e),
                c.push(['message', e]),
                this.$watch('state', () => {
                    u ||
                        (this.updateIframeContent(),
                        setTimeout(() => {
                            if (i && i.contentWindow)
                                try {
                                    i.contentWindow.postMessage(
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
            let e = []
            try {
                e = JSON.parse(JSON.stringify(this.state))
            } catch {
                Array.isArray(this.state) && (e = Array.from(this.state))
            }
            return Array.isArray(e)
                ? e.filter((t) => t && t.type === 'masonBrick')
                : []
        },
        sendMessageToIframe(e) {
            i && i.contentWindow && i.contentWindow.postMessage(e, '*')
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
            let e = this.$el.querySelector('.mason-editor-wrapper')
            e &&
                (e.addEventListener('dragover', (t) => {
                    t.preventDefault()
                }),
                e.addEventListener('drop', (t) => {
                    t.preventDefault()
                    let s = t.dataTransfer.getData('brick')
                    if (s) {
                        let o = this.getBlocksFromState().length
                        this.handleInsertBlock(s, o)
                    }
                }))
        },
        async handleInsertBlock(e, t) {
            this.isInsertingBrick = !0
            try {
                await this.$wire.mountAction(
                    'handleBrick',
                    { id: e, dragPosition: t, mode: 'insert' },
                    { schemaComponent: d },
                )
            } finally {
                this.isInsertingBrick = !1
            }
        },
        async handleEditBlock({ index: e, brickId: t, config: s }) {
            this.isUpdatingBrick = !0
            try {
                await this.$wire.mountAction(
                    'handleBrick',
                    { id: t, config: s, mode: 'edit', blockIndex: e },
                    { schemaComponent: d },
                )
            } finally {
                this.isUpdatingBrick = !1
            }
        },
        handleDeleteBlock(e) {
            let t = this.getBlocksFromState()
            if (e >= 0 && e < t.length) {
                let s = t.filter((n, o) => o !== e)
                this.updateStateFromBlocks(s)
            }
        },
        handleUpdateBlock(e, t) {
            let s = this.getBlocksFromState()
            if (e >= 0 && e < s.length) {
                let n = s.map((o, a) => (a === e ? t : o))
                this.updateStateFromBlocks(n)
            }
        },
        handleMoveBlock(e, t) {
            let s = this.getBlocksFromState()
            if (e < 0 || e >= s.length || t < 0 || t > s.length || e === t)
                return
            let n = s.slice(),
                o = n[e]
            n.splice(e, 1)
            let a
            ;(t === s.length
                ? (a = n.length)
                : t > e
                  ? t === e + 1
                      ? (a = t)
                      : (a = t - 1)
                  : (a = t),
                (a = Math.max(0, Math.min(a, n.length))),
                n.splice(a, 0, o),
                this.updateStateFromBlocks(n),
                setTimeout(() => {
                    this.sendMessageToIframe({ type: 'updateMoveButtons' })
                }, 150))
        },
        updateStateFromBlocks(e) {
            let t = e
            try {
                t = JSON.parse(JSON.stringify(e))
            } catch {
                Array.isArray(e) &&
                    (t = e.map((o) => {
                        try {
                            return JSON.parse(JSON.stringify(o))
                        } catch {
                            return o
                        }
                    }))
            }
            this.state = t
            let s = this.statePath
            this.$wire.set(s, t, !1)
        },
        toggleFullscreen() {
            ;((this.fullscreen = !this.fullscreen),
                this.fullscreen || (this.viewport = 'desktop'))
        },
        toggleViewport(e) {
            this.viewport = e
        },
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen
        },
        saveScrollPosition() {
            if (i && i.contentWindow)
                try {
                    r = {
                        x:
                            i.contentWindow.scrollX ||
                            i.contentWindow.pageXOffset ||
                            0,
                        y:
                            i.contentWindow.scrollY ||
                            i.contentWindow.pageYOffset ||
                            0,
                    }
                } catch {
                    r = { x: 0, y: 0 }
                }
        },
        restoreScrollPosition() {
            if (i && i.contentWindow && (r.x > 0 || r.y > 0))
                try {
                    let e = () => {
                        if (i && i.contentWindow) {
                            let t =
                                i.contentDocument || i.contentWindow.document
                            t && t.readyState === 'complete'
                                ? i.contentWindow.scrollTo(r.x, r.y)
                                : setTimeout(e, 50)
                        }
                    }
                    ;(setTimeout(e, 0), setTimeout(e, 50), setTimeout(e, 100))
                } catch {}
        },
        updatePreview() {
            this.saveScrollPosition()
            let e = this.getBlocksFromState(),
                t = e
            try {
                t = JSON.parse(JSON.stringify(e))
            } catch {
                t = e
            }
            let s = document.createElement('form')
            ;((s.method = 'POST'),
                (s.action = '/mason/preview'),
                (s.target = 'mason-preview-iframe'),
                (s.style.display = 'none'))
            let n = document.createElement('input')
            ;((n.type = 'hidden'),
                (n.name = 'blocks'),
                (n.value = JSON.stringify(t)),
                s.appendChild(n))
            let o = document.createElement('input')
            if (
                ((o.type = 'hidden'),
                (o.name = 'bricks'),
                (o.value = JSON.stringify(k)),
                s.appendChild(o),
                this.previewLayout)
            ) {
                let l = document.createElement('input')
                ;((l.type = 'hidden'),
                    (l.name = 'layout'),
                    (l.value = this.previewLayout),
                    s.appendChild(l))
            }
            let a = document.createElement('input')
            ;((a.type = 'hidden'),
                (a.name = '_token'),
                (a.value =
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || ''),
                s.appendChild(a),
                document.body.appendChild(s),
                s.submit(),
                setTimeout(() => {
                    s.parentNode && s.parentNode.removeChild(s)
                }, 100))
        },
        deselectAllBlocks() {
            this.isUpdatingBrick ||
                this.sendMessageToIframe({ type: 'deselectAllBlocks' })
        },
        destroy() {
            ;((u = !0),
                c.forEach(([e, t]) => {
                    window.removeEventListener(e, t)
                }),
                (c = []),
                (i = null))
        },
    }
}
export { g as default }
