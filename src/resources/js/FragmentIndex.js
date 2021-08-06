/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed

/**
 * Category index class
 */
Craft.FragmentIndex = Craft.BaseElementIndex.extend({
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

        // Update the New Fragment button
        // ---------------------------------------------------------------------

        if (Craft.fragmentTypes.length) {
            // Remove the old button, if there is one
            if (this.$newFragmentBtnGroup) {
                this.$newFragmentBtnGroup.remove();
            }

            // Determine if they are viewing a group that they have permission to create categories in
            var selectedGroup;

            if (selectedSourceHandle) {
                for (i = 0; i < Craft.fragmentTypes.length; i++) {
                    if (Craft.fragmentTypes[i].handle === selectedSourceHandle) {
                        selectedGroup = Craft.fragmentTypes[i];
                        break;
                    }
                }
            }

            this.$newFragmentBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            if (selectedGroup) {
                href = this._getGroupTriggerHref(selectedGroup);
                label = (this.settings.context === 'index' ? Craft.t('app', 'New fragment') : Craft.t('app', 'New {group} fragment', {group: selectedGroup.name}));
                this.$newFragmentBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newFragmentBtnGroup);

                if (this.settings.context !== 'index') {
                    this.addListener(this.$newFragmentBtn, 'click', function(ev) {
                        this._openCreateCategoryModal(ev.currentTarget.getAttribute('data-id'));
                    });
                }

                if (Craft.fragmentTypes.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn',
                    }).appendTo(this.$newFragmentBtnGroup);
                }
            } else {
                this.$newFragmentBtn = $menuBtn = $('<button/>', {
                    type: 'button',
                    class: 'btn submit add icon menubtn',
                    text: Craft.t('app', 'New fragment'),
                }).appendTo(this.$newFragmentBtnGroup);
            }

            if ($menuBtn) {
                var menuHtml = '<div class="menu"><ul>';

                for (i = 0; i < Craft.fragmentTypes.length; i++) {
                    var group = Craft.fragmentTypes[i];

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
    },

    _getGroupTriggerHref: function(group, selectedSourceHandle) {
        if (this.settings.context === 'index') {
            var uri = 'fragments/fragments/' + selectedSourceHandle + '/' + group.handle + '/new';
            if (this.siteId && this.siteId !== Craft.primarySiteId) {
                for (var i = 0; i < Craft.sites.length; i++) {
                    if (Craft.sites[i].id === this.siteId) {
                        uri += '/' + Craft.sites[i].handle;
                    }
                }
            }
            return 'href="' + Craft.getUrl(uri) + '"';
        } else {
            return 'data-id="' + group.id + '"';
        }
    },
});

Craft.registerElementIndexClass('thepixelage\\fragments\\elements\\Fragment', Craft.FragmentIndex);
