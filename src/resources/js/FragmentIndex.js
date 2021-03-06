/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed

/**
 * Category index class
 */
Craft.FragmentIndex = Craft.BaseElementIndex.extend({
    fragmentTypes: [],
    $newFragmentBtnGroup: null,
    $newFragmentBtn: null,

    init: function(elementType, $container, settings) {
        this.on('selectSource', $.proxy(this, 'updateButton'));
        this.on('selectSite', $.proxy(this, 'updateButton'));
        this.base(elementType, $container, settings);
    },

    updateButton: function() {
        if (!this.$source) {
            return;
        }

        // Get the handle of the selected source
        var selectedSourceHandle = this.$source.data('handle');

        var i, href, label;

        this.fragmentTypes = Craft.fragmentTypes[this.$source.data('key')];

        // Update the New Fragment button
        // ---------------------------------------------------------------------

        if (this.fragmentTypes.length) {
            // Remove the old button, if there is one
            if (this.$newFragmentBtnGroup) {
                this.$newFragmentBtnGroup.remove();
            }

            // Determine if they are viewing a group that they have permission to create categories in
            var selectedGroup = null;

            this.$newFragmentBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            this.$newFragmentBtn = $menuBtn = $('<button/>', {
                type: 'button',
                class: 'btn submit add icon menubtn',
                text: Craft.t('app', 'New fragment'),
            }).appendTo(this.$newFragmentBtnGroup);

            if ($menuBtn) {
                var menuHtml = '<div class="menu"><ul>';

                for (i = 0; i < this.fragmentTypes.length; i++) {
                    var group = this.fragmentTypes[i];

                    if (this.settings.context === 'index' || group !== selectedGroup) {
                        href = this._getGroupTriggerHref(group, selectedSourceHandle);
                        label = (this.settings.context === 'index' ? group.name : Craft.t('app', 'New {group} fragment', {group: group.name}));
                        menuHtml += '<li><a ' + href + '>' + Craft.escapeHtml(label) + '</a></li>';
                    }
                }

                menuHtml += '</ul></div>';

                $(menuHtml).appendTo(this.$newFragmentBtnGroup);
                var menuBtn = new Garnish.MenuBtn($menuBtn);

                if (this.settings.context !== 'index') {
                    menuBtn.on('optionSelect', $.proxy(function(ev) {
                        this._openCreateCategoryModal(ev.option.getAttribute('data-id'));
                    }, this));
                }
            }

            this.addButton(this.$newFragmentBtnGroup);
        }

        // Update the URL if we're on the Entries index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index' && typeof history !== 'undefined') {
            var uri = 'fragments/fragments';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            history.replaceState({}, '', Craft.getUrl(uri));
        }
    },

    _getGroupTriggerHref: function(group, selectedSourceHandle) {
        if (this.settings.context === 'index') {
            var uri = 'fragments/fragments/' + selectedSourceHandle + '/' + group.handle + '/new';
            let params = {};
            if (this.siteId) {
                for (var i = 0; i < Craft.sites.length; i++) {
                    if (Craft.sites[i].id === this.siteId) {
                        params.site = Craft.sites[i].handle;
                    }
                }
            }
            return 'href="' + Craft.getUrl(uri, params) + '"';
        } else {
            return 'data-id="' + group.id + '"';
        }
    },
});

Craft.registerElementIndexClass('thepixelage\\fragments\\elements\\Fragment', Craft.FragmentIndex);
