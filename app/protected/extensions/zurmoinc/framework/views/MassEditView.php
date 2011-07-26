<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2011 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 113 McHenry Road Suite 207,
     * Buffalo Grove, IL 60089, USA. or at email address contact@zurmo.com.
     ********************************************************************************/

    /**
     * The base View for a module's mass edit view.
     */
    abstract class MassEditView extends EditView
    {
        /**
         * Array of booleans indicating
         * which attributes are currently trying to
         * be mass updated
         */
        protected $activeAttributes;

        protected $alertMessage;

        protected $selectedRecordCount;

        /**
         * Constructs a detail view specifying the controller as
         * well as the model that will have its mass edit displayed.
         */
        public function __construct($controllerId, $moduleId, RedBeanModel $model, $activeAttributes, $selectedRecordCount, $alertMessage = null)
        {
            assert('is_array($activeAttributes)');
            $this->controllerId        = $controllerId;
            $this->moduleId            = $moduleId;
            $this->model               = $model;
            $this->modelClassName      = get_class($model);
            $this->modelId             = $model->id;
            $this->activeAttributes    = $activeAttributes;
            $this->selectedRecordCount = $selectedRecordCount;
            $this->alertMessage        = $alertMessage;
        }

        protected function renderContent()
        {
            $content = '<div class="wide form">';
            $clipWidget = new ClipWidget();
            list($form, $formStart) = $clipWidget->renderBeginWidget(
                                                                'ZurmoActiveForm',
                                                                array('id' => 'edit-form', 'enableAjaxValidation' => false)
                                                            );
            $content .= $formStart;
            if (!empty($this->alertMessage))
            {
                $content .= HtmlNotifyUtil::renderAlertBoxByMessage($this->alertMessage);
            }
            $content .= $this->renderHighlightBox();
            $content .= '<div>';
            $content .= $this->renderActionElementBar(true);
            $content .= '</div>';
            $content .= $this->renderFormLayout($form);
            $formEnd = $clipWidget->renderEndWidget();
            $content .= $formEnd;
            $content .= '</div>';
            return $content;
        }

        protected function renderHighlightBox()
        {
            $message = '<strong>' . $this->selectedRecordCount . '</strong>&#160;' .
                    LabelUtil::getUncapitalizedRecordLabelByCount($this->selectedRecordCount) . ' ' .
                    Yii::t('Default', 'selected for updating.');
            return HtmlNotifyUtil::renderHighlightBoxByMessage($message);
        }

        /**
         * Render a form layout.
         *  Gets appropriate meta data and loops through it. Builds form content
         *  as it loops through. For each element in the form it calls the appropriate
         *  Element class.
         *   @param $form If the layout is editable, then pass a $form otherwise it can
         *  be null.
         * @return A string containing the element's content.
          */
        protected function renderFormLayout($form = null)
        {
            $metadata = self::getMetadata();
            $massEditScript = '';
            $content = '<table>';
            $content .= '<colgroup>';
            $content .= '<col class="col-checkbox"/><col style="width:20%" /><col style="width:80%" />';
            $content .= '</colgroup>';
            $content .= '<tbody>';
            //loop through each panel
            foreach ($metadata['global']['panels'] as $panel)
            {
                foreach ($panel['rows'] as $row)
                {
                    $content .= '<tr>';
                    foreach ($row['cells'] as $cell)
                    {
                        if (is_array($cell['elements']))
                        {
                            foreach ($cell['elements'] as $elementInformation)
                            {
                                $elementclassname = $elementInformation['type'] . 'Element';
                                $params = array_slice($elementInformation, 2);
                                if (empty($this->activeAttributes[$elementInformation['attributeName']]))
                                {
                                    $params['disabled'] = true;
                                    $checked = false;
                                }
                                else
                                {
                                    $checked = true;
                                }
                                $element  = new $elementclassname($this->model, $elementInformation['attributeName'], $form, $params);
                                $content .= $this->renderActiveAttributesCheckBox($element->getEditableNameIds(), $elementInformation['attributeName'], $checked);
                                $content .= $element->render();
                            }
                        }
                    }
                    $content .= '</tr>';
                }
            }
            $content .= '</tbody>';
            $content .= '</table>';
            return $content;
        }

        protected function renderActiveAttributesCheckBox($elementIds, $attributeName, $checked)
        {
            $checkBoxHtmlOptions = array();
            $checkBoxHtmlOptions['id'] = "MassEdit_" . $attributeName;
            $enableInputsScript = "";
            $disableInputsScript = "";
            foreach ($elementIds as $id)
            {
                $enableInputsScript  .= "$('#" . $id . "').attr('disabled', ''); \n";
                $enableInputsScript .= "if ($('#" . $id . "').attr('type') != 'button')
                {
                    if ($('#" . $id . "').attr('href') != undefined)
                    {
                        $('#" . $id . "').css('display', '');
                    }
                }; \n";
                $disableInputsScript .= "$('#" . $id . "').attr('disabled', 'disabled'); \n";
                $disableInputsScript .= "if ($('#" . $id . "').attr('type') != 'button')
                {
                    if ($('#" . $id . "').attr('href') != undefined)
                    {
                        $('#" . $id . "').css('display', 'none');
                    }
                    $('#" . $id . "').val('');
                }; \n";
            }
            $massEditScript = <<<END
$('#{$checkBoxHtmlOptions['id']}').click(function()
    {
        if (this.checked)
        {
            $enableInputsScript
        }
        else
        {
            $disableInputsScript
        }
    }
);
END;
            Yii::app()->clientScript->registerScript($checkBoxHtmlOptions['id'], $massEditScript);
            return "<th>" . CHtml::checkBox("MassEdit[" . $attributeName . "]", $checked, $checkBoxHtmlOptions) ."</th>  \n";
        }

        public static function getDesignerRulesType()
        {
            return 'MassEditView';
        }
    }
?>