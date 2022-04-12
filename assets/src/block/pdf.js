import {registerBlockType} from "@wordpress/blocks";
import {InspectorControls, useBlockProps, MediaPlaceholder} from "@wordpress/block-editor";
import {Panel, PanelBody, RangeControl, TextControl, SelectControl} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import Icon from "../components/Icon";
import ServerSideRender from '@wordpress/server-side-render';


const Edit = (props) => {
    const {attributes, setAttributes} = props;
    const blockProps = useBlockProps();
    const afterPDFTypeChange = (pdf_type) => {
        setAttributes({pdf_type: pdf_type});

    }
    const onSelectMedia = (media) => {

        setAttributes({
            pdf_id: typeof media.id !== "undefined" ? media.id : 0

        });

    }
    var upload_button_text = "Add PDF Document";

    if (attributes.pdf_id > 0) {
        upload_button_text = "Replace PDF Document";
    }
    return (
        <div {...blockProps}>
            <ServerSideRender
                block="document-engine/pdf"
                attributes={attributes}
            />
            <InspectorControls key="setting">
                <div id="document-engine-controls">
                    <Panel>
                        <PanelBody title={__('PDF Viewer Settings', 'document-engine')} initialOpen={true}>
                            <SelectControl
                                label={__('PDF Type', 'document-engine')}
                                value={attributes.pdf_type}
                                options={DocumentEnginePDFViewer.all_pdf_types}
                                onChange={(pdf_type) => afterPDFTypeChange(pdf_type)}
                            />
                            {attributes.pdf_type === "file" ?
                                <MediaPlaceholder
                                    icon="pdf"
                                    labels={{
                                        title: upload_button_text
                                    }}
                                    className="block-image"
                                    onSelect={onSelectMedia}
                                    allowedTypes={['application/pdf']}
                                />
                                : <
                                    TextControl
                                    label={__('PDF URL', 'document-engine')}
                                    value={attributes.pdf_url}
                                    onChange={(pdf_url) => setAttributes({pdf_url: pdf_url})}
                                    min={1}
                                    max={2000}
                                />}
                            <SelectControl
                                label={__('Width Unit', 'document-engine')}
                                value={attributes.width_unit}
                                options={DocumentEnginePDFViewer.all_units}
                                onChange={(width_unit) => setAttributes({width_unit: width_unit})}
                            />
                            <
                                RangeControl
                                label={__('Width Size', 'document-engine')}
                                value={attributes.width_size}
                                onChange={(width_size) => setAttributes({width_size: width_size})}
                                min={1}
                                max={2000}
                            />
                            <SelectControl
                                label={__('Height Unit', 'document-engine')}
                                value={attributes.height_unit}
                                options={DocumentEnginePDFViewer.all_units}
                                onChange={(height_unit) => setAttributes({height_unit: height_unit})}
                            />
                            <RangeControl
                                label={__('Height Size', 'document-engine')}
                                value={attributes.height_size}
                                onChange={(height_size) => setAttributes({height_size: height_size})}
                                min={1}
                                max={2000}
                            />

                        </PanelBody>
                    </Panel>
                </div>
            </InspectorControls>
        </div>
    );
}

registerBlockType('document-engine/pdf', {
    apiVersion: 2,
    title: __('PDF Viewer', 'document-engine'),
    description: __('This block is use to show the pdf file', 'document-engine'),
    icon: Icon,
    keywords: [__("pdf"), __("pdf viewer"), __("pdf wordpress"), __("document engine")],
    edit: Edit,
    category: 'document-engine',

});
