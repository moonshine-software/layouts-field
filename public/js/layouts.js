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
                {
                    handle: '.handle'
                },
                function(evt) {
                    t._reindex()
                }
            )
        },
        add(name) {
            const t = this

            MoonShine.request(t, t.url, 'post', {
                field: t.column,
                name: name,
            }, {}, {
                beforeCallback: function(data) {
                    t.blocksContainer.innerHTML += data.html
                    t._reindex()
                }
            })
        },
        remove() {
            this.$el.closest('._layouts-block').remove()
            this._reindex()
        },
        _reindex() {
            const t = this

            this.$nextTick(function() {
                MoonShine.iterable.reindex(
                    t.blocksContainer,
                    '._layouts-block'
                )
            })
        }
    }))
})
