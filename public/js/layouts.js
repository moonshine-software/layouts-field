document.addEventListener('alpine:init', () => {
    Alpine.data('layouts', (url, column) => ({
        url: url,
        column: column,
        root: null,
        blocksContainer: null,

        init() {
            this.root = this.$root
            this.blocksContainer = this.root.querySelector('._layouts-blocks')
            this._reindex()

            const t = this

            MoonShine.iterable.sortable(
                this.blocksContainer,
                null,
                'layouts',
                null,
                { handle: '.handle' },
                function() { t._reindex() },
            )
        },

        add(name) {
            const t = this

            let counts = {}
            document.querySelectorAll('._layout-value').forEach(function(l) {
                counts[l.value] = (counts[l.value] || 0) + 1
            })

            MoonShine.request(t, t.url, 'post', {
                field: t.column,
                name: name,
                counts: counts,
            }, {}, {
                afterResponse: function(data) {
                    const temp = document.createElement('div')
                    temp.innerHTML = data.html ?? data.htmlData[0].html ?? ''

                    while (temp.firstChild) {
                        t.blocksContainer.appendChild(temp.firstChild)
                    }

                    t._reindex()

                    t.$nextTick(function() {
                        document.dispatchEvent(
                            new CustomEvent('layouts:block-added', {
                                bubbles: true,
                                detail: { name: name, column: t.column },
                            }),
                        )
                    })
                },
            })
        },

        remove() {
            this.$el.closest('._layouts-block').remove()
            this._reindex()
        },

        /**
         * Orchestrates reindexing in two phases:
         *
         * 1. MoonShine iterable.reindex — handles level-0 field names
         *    and position displays. Async (uses await $nextTick internally).
         *
         * 2. _reindexFields (deferred via setTimeout 0) — runs after
         *    iterable.reindex fully completes, computing correct indices
         *    for ALL nesting levels from DOM positions.
         */
        _reindex() {
            const t = this

            this.$nextTick(function() {
                MoonShine.iterable.reindex(
                    t.blocksContainer,
                    '._layouts-block',
                )

                setTimeout(function() {
                    t._reindexFields()
                }, 0)
            })
        },

        /**
         * Recomputes and applies block indices to position displays
         * and field name attributes across all nesting levels.
         *
         * Reads indices from DOM position (not data-r-index) so it's
         * independent of iterable.reindex timing.
         */
        _reindexFields() {
            const t = this

            function countBlocksBefore(block) {
                const parent = block.parentElement
                if (!parent) return 0

                let count = 0
                for (let i = 0; i < parent.children.length; i++) {
                    if (parent.children[i] === block) return count
                    if (parent.children[i].classList?.contains('_layouts-block')) {
                        count++
                    }
                }
                return count
            }

            function findPositionDisplay(block) {
                return block.querySelector(
                    ':scope > .accordion-item > .accordion-btn [data-increment-position]',
                )
            }

            function resolveIndexChain(startBlock) {
                const indices = []
                let searchFrom = startBlock

                while (searchFrom) {
                    const block = searchFrom.closest('._layouts-block')
                    if (!block) break

                    indices.unshift(countBlocksBefore(block) + 1)

                    const layoutsRoot = block.closest('[data-top-level]')
                    if (!layoutsRoot) break
                    searchFrom = layoutsRoot.parentElement
                }

                return indices
            }

            function resolveName(template, indices) {
                let result = template
                for (let i = 0; i < indices.length; i++) {
                    result = result.split('${index' + i + '}').join(indices[i])
                }
                return result
            }

            function applyIndices(block) {
                const indices = resolveIndexChain(block)

                const positionEl = findPositionDisplay(block)
                if (positionEl) {
                    const position = indices[indices.length - 1]
                    positionEl.setAttribute('data-r-index', position)
                    positionEl.innerHTML = position
                }

                block.querySelectorAll('[data-level]').forEach(function(el) {
                    const level = parseInt(el.getAttribute('data-level'))
                    if (isNaN(level) || level < 1) return

                    const dataName = el.getAttribute('data-name')
                    if (dataName && dataName.includes('${index')) {
                        el.setAttribute('name', resolveName(dataName, indices))
                    }

                    const validationField = el.getAttribute('data-validation-field')
                    if (validationField && validationField.includes('${index')) {
                        el.setAttribute('data-validation-field', resolveName(validationField, indices))
                    }
                })
            }

            const layoutRoots = [t.root]
            t.root.querySelectorAll('[data-top-level]').forEach(function(el) {
                if (el !== t.root) {
                    layoutRoots.push(el)
                }
            })

            layoutRoots.forEach(function(layoutRoot) {
                const container = layoutRoot.querySelector(':scope > ._layouts-blocks')
                if (!container) return

                Array.from(container.children).forEach(function(block) {
                    if (block.classList?.contains('_layouts-block')) {
                        applyIndices(block)
                    }
                })
            })
        },
    }))
})
