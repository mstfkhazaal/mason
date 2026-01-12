export default function masonComponent({
    key,
    livewireId,
    state,
    statePath,
    placeholder = null,
    isDisabled,
    isLiveDebounced,
    isLiveOnBlur,
    liveDebounce,
    dblClickToEdit,
    deleteBrickButtonIconHtml,
    editBrickButtonIconHtml,
    insertAboveBrickButtonIconHtml,
    insertBelowBrickButtonIconHtml,
    moveBrickUpButtonIconHtml,
    moveBrickDownButtonIconHtml,
    bricks = [],
    previewLayout = null,
}) {
    let iframe = null
    let eventListeners = []
    let isDestroyed = false
    let previewUrl = null
    let savedScrollPosition = { x: 0, y: 0 }
    let focusCheckInterval = null

    return {
        state: state,
        statePath: statePath,
        fullscreen: false,
        viewport: 'desktop',
        isFocused: false,
        sidebarOpen: true,
        isUpdatingBrick: false,
        isInsertingBrick: false,
        previewUrl: null,
        previewLayout: previewLayout,

        async init() {
            // Wait for iframe to load
            this.$nextTick(() => {
                iframe = this.$refs.previewIframe

                if (iframe) {
                    // Load initial content via form submission
                    this.updatePreview()
                    
                    iframe.addEventListener('load', () => {
                        // Restore scroll position after iframe loads
                        this.restoreScrollPosition()
                        
                        // Send postMessage once iframe is loaded with config
                        this.sendMessageToIframe({ 
                            type: 'setContent', 
                            blocks: this.getBlocksFromState(),
                            dblClickToEdit: dblClickToEdit
                        })
                        
                        // Update move buttons after iframe loads
                        setTimeout(() => {
                            this.sendMessageToIframe({ type: 'updateMoveButtons' })
                        }, 100)
                    })
                }
            })

            // Listen for messages from iframe
            const messageHandler = (event) => {
                // Security: verify origin if needed
                // if (event.origin !== window.location.origin) return

                const { type, ...data } = event.data

                switch (type) {
                    case 'ready':
                        this.sendMessageToIframe({ type: 'setContent', blocks: this.getBlocksFromState() })
                        break
                    case 'editBlock':
                        this.handleEditBlock(data)
                        break
                    case 'deleteBlockRequest':
                        this.handleDeleteBlock(data.index)
                        break
                    case 'insertBlockRequest':
                        this.handleInsertBlock(data.brickId, data.position)
                        break
                    case 'updateBlockRequest':
                        this.handleUpdateBlock(data.index, data.brick)
                        break
                    case 'moveBlockRequest':
                        this.handleMoveBlock(data.from, data.to)
                        break
                    case 'blockSelected':
                        // Set focus when a block is selected in the preview
                        // Focus the wrapper so :focus-within works, then let tracker pick it up
                        if (this.$el) {
                            this.$el.focus()
                            // The focus tracker will detect this on next check
                            // Set it immediately to avoid delay
                            this.isFocused = true
                        }
                        break
                }
            }

            window.addEventListener('message', messageHandler)
            eventListeners.push(['message', messageHandler])

            // Watch for state changes
            this.$watch('state', () => {
                if (isDestroyed) return
                this.updateIframeContent()
                // Update move buttons after iframe updates (with delay for iframe to load)
                setTimeout(() => {
                    if (iframe && iframe.contentWindow) {
                        try {
                            iframe.contentWindow.postMessage({ type: 'updateMoveButtons' }, '*')
                        } catch (e) {
                            // Ignore cross-origin errors
                        }
                    }
                }, 200)
            })

            // Watch for focus changes and notify iframe
            this.$watch('isFocused', (focused) => {
                if (isDestroyed) return
                this.sendMessageToIframe({ 
                    type: 'setParentFocus', 
                    focused: focused 
                })
            })

            // Handle drag and drop from sidebar
            this.setupDragAndDrop()

            // Track focus state on the wrapper element
            this.setupFocusTracking()
        },


        getBlocksFromState() {
            if (!this.state) {
                return []
            }

            // Convert Proxy to plain array to avoid cloning issues
            let stateArray = []
            try {
                // Use JSON parse/stringify to convert Proxy to plain object
                stateArray = JSON.parse(JSON.stringify(this.state))
            } catch (e) {
                // Fallback: try to convert manually
                if (Array.isArray(this.state)) {
                    stateArray = Array.from(this.state)
                }
            }

            if (!Array.isArray(stateArray)) {
                return []
            }

            return stateArray.filter(block => block && block.type === 'masonBrick')
        },

        sendMessageToIframe(message) {
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage(message, '*')
            }
        },

        updateIframeContent() {
            this.updatePreview()
            // Also send config update to iframe
            this.sendMessageToIframe({ 
                type: 'setConfig',
                dblClickToEdit: dblClickToEdit
            })
        },

        setupDragAndDrop() {
            // The sidebar already has draggable="true" and dragstart handler
            // We just need to handle the drop on the iframe
            const iframeContainer = this.$el.querySelector('.mason-editor-wrapper')
            
            if (iframeContainer) {
                iframeContainer.addEventListener('dragover', (e) => {
                    e.preventDefault()
                    this.isFocused = true
                })

                iframeContainer.addEventListener('drop', (e) => {
                    e.preventDefault()
                    const brickId = e.dataTransfer.getData('brick')
                    
                    if (brickId) {
                        // Calculate drop position (we'll insert at the end for now)
                        const blocks = this.getBlocksFromState()
                        const position = blocks.length
                        
                        this.handleInsertBlock(brickId, position)
                    }
                })
            }
        },

        setupFocusTracking() {
            // Track focus on the wrapper element and its children using :focus-within
            const wrapper = this.$el
            
            if (wrapper) {
                const checkFocus = () => {
                    if (isDestroyed) return false
                    const hasFocus = wrapper.matches(':focus-within')
                    if (this.isFocused !== hasFocus) {
                        this.isFocused = hasFocus
                    }
                    return hasFocus
                }
                
                // Check initial focus state
                this.$nextTick(() => {
                    checkFocus()
                })
                
                // Check focus state periodically (more reliable than focus events for complex interactions)
                focusCheckInterval = setInterval(checkFocus, 100)
            }
        },

        async handleInsertBlock(brickId, position) {
            this.isInsertingBrick = true

            try {
                await this.$wire.mountAction(
                    'handleBrick',
                    { id: brickId, dragPosition: position, mode: 'insert' },
                    { schemaComponent: key },
                )
            } finally {
                this.isInsertingBrick = false
            }
        },

        async handleEditBlock({ index, brickId, config }) {
            this.isUpdatingBrick = true

            try {
                await this.$wire.mountAction(
                    'handleBrick',
                    {
                        id: brickId,
                        config,
                        mode: 'edit',
                        blockIndex: index,
                    },
                    { schemaComponent: key },
                )
            } finally {
                this.isUpdatingBrick = false
            }
        },

        handleDeleteBlock(index) {
            const blocks = this.getBlocksFromState()
            
            if (index >= 0 && index < blocks.length) {
                // Create a new array without the deleted block
                const newBlocks = blocks.filter((_, i) => i !== index)
                
                this.updateStateFromBlocks(newBlocks)
            }
        },

        handleUpdateBlock(index, brick) {
            const blocks = this.getBlocksFromState()
            
            if (index >= 0 && index < blocks.length) {
                // Create a new array with the updated block
                const newBlocks = blocks.map((block, i) => i === index ? brick : block)
                
                this.updateStateFromBlocks(newBlocks)
            }
        },

        handleMoveBlock(from, to) {
            const blocks = this.getBlocksFromState()
            
            // Validate indices
            if (from < 0 || from >= blocks.length) return
            // Allow to be up to blocks.length (for moving to the end)
            if (to < 0 || to > blocks.length) return
            if (from === to) return
            
            // Create a new array with moved block (blocks are already plain from getBlocksFromState)
            const newBlocks = blocks.slice() // Use slice instead of spread for safety
            const moved = newBlocks[from]
            newBlocks.splice(from, 1)
            
            // Adjust target index after removing the block
            // The 'to' index is in the original array before removal
            // After removal, indices shift for items after 'from'
            let adjustedTo
            if (to === blocks.length) {
                // Moving to the end - insert at the end of the new array
                adjustedTo = newBlocks.length
            } else if (to > from) {
                // Moving down - target index shifts down by 1 because we removed an item before it
                adjustedTo = to - 1
            } else {
                // Moving up - no adjustment needed (target is before the removed item)
                adjustedTo = to
            }
            
            // Clamp to valid range (0 to newBlocks.length)
            adjustedTo = Math.max(0, Math.min(adjustedTo, newBlocks.length))
            
            newBlocks.splice(adjustedTo, 0, moved)
            
            this.updateStateFromBlocks(newBlocks)
            
            // Update move buttons in iframe after move completes
            setTimeout(() => {
                this.sendMessageToIframe({ type: 'updateMoveButtons' })
            }, 150)
        },

        updateStateFromBlocks(blocks) {
            // Ensure blocks is a plain array (not Proxy) before updating
            let plainBlocks = blocks
            try {
                // Convert to plain objects via JSON (handles Proxy objects)
                plainBlocks = JSON.parse(JSON.stringify(blocks))
            } catch (e) {
                // If conversion fails, try to create a new array manually
                if (Array.isArray(blocks)) {
                    plainBlocks = blocks.map(block => {
                        try {
                            return JSON.parse(JSON.stringify(block))
                        } catch {
                            return block
                        }
                    })
                }
            }
            
            // Update the state with new blocks array
            this.state = plainBlocks
            // Use the statePath from the component
            const path = this.statePath
            this.$wire.set(path, plainBlocks, false)
        },

        toggleFullscreen() {
            this.fullscreen = !this.fullscreen

            if (!this.fullscreen) {
                this.viewport = 'desktop'
            }
        },

        toggleViewport(viewport) {
            this.viewport = viewport
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen
        },

        saveScrollPosition() {
            // Save scroll position from iframe's content window
            if (iframe && iframe.contentWindow) {
                try {
                    savedScrollPosition = {
                        x: iframe.contentWindow.scrollX || iframe.contentWindow.pageXOffset || 0,
                        y: iframe.contentWindow.scrollY || iframe.contentWindow.pageYOffset || 0,
                    }
                } catch (e) {
                    // Cross-origin or other error, reset to 0
                    savedScrollPosition = { x: 0, y: 0 }
                }
            }
        },

        restoreScrollPosition() {
            // Restore scroll position in iframe's content window
            if (iframe && iframe.contentWindow && (savedScrollPosition.x > 0 || savedScrollPosition.y > 0)) {
                try {
                    // Use multiple timeouts to ensure content is fully loaded and rendered
                    const restore = () => {
                        if (iframe && iframe.contentWindow) {
                            const doc = iframe.contentDocument || iframe.contentWindow.document
                            if (doc && doc.readyState === 'complete') {
                                iframe.contentWindow.scrollTo(savedScrollPosition.x, savedScrollPosition.y)
                            } else {
                                // If not ready, try again
                                setTimeout(restore, 50)
                            }
                        }
                    }
                    
                    // Try immediately, then with delays
                    setTimeout(restore, 0)
                    setTimeout(restore, 50)
                    setTimeout(restore, 100)
                } catch (e) {
                    // Cross-origin or other error, ignore
                }
            }
        },

        updatePreview() {
            // Save scroll position before updating
            this.saveScrollPosition()
            
            // Update iframe using form submission (more reliable than srcdoc)
            const blocks = this.getBlocksFromState()
            
            // Ensure blocks are plain objects (not Proxy) before stringifying
            let plainBlocks = blocks
            try {
                plainBlocks = JSON.parse(JSON.stringify(blocks))
            } catch (e) {
                // If JSON conversion fails, use blocks as-is (shouldn't happen after getBlocksFromState)
                plainBlocks = blocks
            }
            
            // Create a form that will submit to the iframe
            const form = document.createElement('form')
            form.method = 'POST'
            form.action = '/mason/preview'
            form.target = 'mason-preview-iframe'
            form.style.display = 'none'

            // Add blocks data
            const blocksInput = document.createElement('input')
            blocksInput.type = 'hidden'
            blocksInput.name = 'blocks'
            blocksInput.value = JSON.stringify(plainBlocks)
            form.appendChild(blocksInput)

            // Add bricks data
            const bricksInput = document.createElement('input')
            bricksInput.type = 'hidden'
            bricksInput.name = 'bricks'
            bricksInput.value = JSON.stringify(bricks)
            form.appendChild(bricksInput)

            // Add preview layout if provided
            if (this.previewLayout) {
                const layoutInput = document.createElement('input')
                layoutInput.type = 'hidden'
                layoutInput.name = 'layout'
                layoutInput.value = this.previewLayout
                form.appendChild(layoutInput)
            }

            // Add CSRF token
            const csrfInput = document.createElement('input')
            csrfInput.type = 'hidden'
            csrfInput.name = '_token'
            csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content || ''
            form.appendChild(csrfInput)

            // Append form to body, submit, then remove
            document.body.appendChild(form)
            form.submit()
            
            // Remove form after a short delay
            setTimeout(() => {
                if (form.parentNode) {
                    form.parentNode.removeChild(form)
                }
            }, 100)
        },

        destroy() {
            isDestroyed = true

            eventListeners.forEach(([eventName, handler]) => {
                window.removeEventListener(eventName, handler)
            })
            eventListeners = []

            if (focusCheckInterval) {
                clearInterval(focusCheckInterval)
                focusCheckInterval = null
            }

            iframe = null
        },
    }
}
