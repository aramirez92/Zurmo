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
     * Abstraction of the various view elements. Examples include Text fields,
     * checkboxes, and date fields.
     *
     * Element can render an editable or non-editable version and will populate
     * information based on the provided attribute and model.
     */
    abstract class Element
    {
        protected $model;
        protected $attribute;
        protected $form;
        protected $params;
        public $editableTemplate = '<th>{label}</th><td colspan="{colspan}">{content}{error}</td>';
        public $nonEditableTemplate = '<th>{label}</th><td colspan="{colspan}">{content}</td>';

        /**
         * Constructs the element specifying the model and attribute.
         * In the case of needing to show editable information, a form is
         * also provided.
         * @param $form Optional. If supplied an editable element will be rendered.
         * @param $params Can have additional parameters for use.
         *               'wide' true or false is an example parameter
         */
        public function __construct($model, $attribute, $form = null, array $params = array())
        {
            assert('$attribute == null || is_string($attribute)');
            assert('is_array($params)');
            $this->model     = $model;
            $this->attribute = $attribute;
            $this->form      = $form;
            $this->params    = $params;
        }

        /**
         * Generates the element content.
         * @return A string containing the element's content.
         */
        public function render()
        {
            $className = get_called_class();
            if ($this->form === null || $className::isReadOnly())
            {
                return $this->renderNonEditable();
            }
            return $this->renderEditable();
        }

        /**
         * Generate the element label content
         * @return A string containing the element's label
         */
        protected function renderLabel()
        {
            if ($this->form === null)
            {
                return $this->getFormattedAttributeLabel();
            }
            return $this->form->labelEx($this->model, $this->attribute);
        }

        protected function getFormattedAttributeLabel()
        {
            return Yii::app()->format->text($this->model->getAttributeLabel($this->attribute));
        }

        /**
         * Generate the error content. Used by editable content
         * @return error content
         */
        protected function renderError()
        {
            return $this->form->error($this->model, $this->attribute);
        }

        /**
         * Generate editable version of the element
         * includes the lable, control, and error content
         * @return A string containing the element's content.
         */
        protected function renderEditable()
        {
            $data = array();
            $data['label'] = $this->renderLabel();
            $data['content'] = $this->renderControlEditable();
            $data['error'] = $this->renderError();
            $data['colspan'] = $this->getColumnSpan();
            return $this->resolveContentTemplate($this->editableTemplate, $data);
        }

        /**
         * Generate non-editable version of the element
         * includes label and control elements
         * @return A string containing the element's content.
         */
        protected function renderNonEditable()
        {
            $data = array();
            $data['label'] = $this->renderLabel();
            $data['content'] = $this->renderControlNonEditable();
            $data['colspan'] = $this->getColumnSpan();
            return $this->resolveContentTemplate($this->nonEditableTemplate, $data);
        }

        abstract protected function renderControlEditable();

        abstract protected function renderControlNonEditable();

        /**
         * Determines correct column span based on params 'wide' value
         */
        protected function getColumnSpan()
        {
            if (ArrayUtil::getArrayValue($this->params, 'wide'))
            {
                return 3;
            }
            else
            {
                return 1;
            }
        }

        protected function getDisabledValue()
        {
            if (isset($this->params['disabled']) && $this->params['disabled'])
            {
                return 'disabled';
            }
            return null;
        }

        /**
         * Get the collection of id/names of inputs and other
         * parts of the element.
         */
        public function getEditableNameIds()
        {
            $htmlOptions = array();
            CHtml::resolveNameID($this->model, $this->attribute, $htmlOptions);
            return array(
                $htmlOptions['id']
            );
        }

        public static function getDisplayName()
        {
            return Yii::t("Default", get_class());
        }

        public static function isReadOnly()
        {
            return false;
        }

        /**
         * Resolves the editable or non-editable template
         * with data.
         * @return string. resolved $template
         */
        protected function resolveContentTemplate($template, $data)
        {
            assert('is_string($template)');
            assert('is_array($data)');
            $preparedContent = array();
            foreach ($data as $templateVar => $content)
            {
                $preparedContent["{" . $templateVar . "}"] = $content;
            }
            return strtr($template, $preparedContent);
        }

        public function getAttribute()
        {
            return $this->attribute;
        }
    }
?>