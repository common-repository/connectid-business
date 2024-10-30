( function( blocks, i18n, element ) {
            var el = element.createElement;
            var __ = i18n.__;
        
            var blockStyle = {
                padding: '5px 2px',
            };
        
            blocks.registerBlockType('socb-gutenberg/request-portal-link', {
                title: __( 'Request Portal Link', 'socb-cbw-sl' ),
                icon: 'admin-links',
                category: 'widgets',
                example: {},
                edit: function() {
                    return el(
            'p',
            { 
                className: 'block-request-portal-link'
            },
            el('a', {
                style: blockStyle,
                target: '_blank',
                href: 'https://business.connectid.io/requestportal/company/6e659b25-ff1e-4d60-ab0d-e4e2f32c2eff/request'
            },'Request for data')
            )
                },
                save: function() {
                    return el(
            'p',
            { 
                className: 'block-request-portal-link'
            },
            el('a', {
                style: blockStyle,
                target: '_blank',
                href: 'https://business.connectid.io/requestportal/company/6e659b25-ff1e-4d60-ab0d-e4e2f32c2eff/request'
            },'Request for data')
            )
                },
            } );
        } )( window.wp.blocks, window.wp.i18n, window.wp.element );