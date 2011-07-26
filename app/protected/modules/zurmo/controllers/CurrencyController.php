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
     * Controller Class for managing currency actions.
     *
     */
    class ZurmoCurrencyController extends ZurmoModuleController
    {
        public function filters()
        {
            return array(
                array(
                    ZurmoBaseController::RIGHTS_FILTER_PATH,
                    'moduleClassName' => 'ZurmoModule',
                    'rightName' => ZurmoModule::RIGHT_ACCESS_CURRENCY_CONFIGURATION,
               ),
            );
        }

        public function actionIndex()
        {
            $this->actionConfigurationList();
        }

        public function actionConfigurationList()
        {
            $redirectUrlParams = array('/zurmo/' . $this->getId() . '/ConfigurationList',);
            $currency          = new Currency();
            $currency = $this->attemptToSaveModelFromPost($currency, $redirectUrlParams);
            $view = new CurrencyTitleBarConfigurationListAndCreateView(
                            $this->getId(),
                            $this->getModule()->getId(),
                            $currency,
                            Currency::getAll());
            $view = new ZurmoConfigurationPageView($this, $view);
            echo $view->render();
        }

        /**
         * Override to support getting the rate of the currency to the base currency by a web-service.
         */
        protected function attemptToSaveModelFromPost($model, $redirectUrlParams = null)
        {
            assert('$redirectUrlParams == null || is_array($redirectUrlParams)');
            $postVariableName = get_class($model);
            if (isset($_POST[$postVariableName]))
            {
                $model->setAttributes($_POST[$postVariableName]);
                if ($model->rateToBase == null && $model->code != null)
                {
                    $currencyHelper = Yii::app()->currencyHelper;
                    $rate           = (float)$currencyHelper->getConversionRateToBase($model->code);
                    if ($currencyHelper->getWebServiceErrorCode() == $currencyHelper::ERROR_INVALID_CODE)
                    {
                        $model->addError('code', Yii::t('Default', 'The currency code is invalid'));
                        $currencyHelper->resetErrors();
                        return $model;
                    }
                    elseif ($currencyHelper->getWebServiceErrorCode() == $currencyHelper::ERROR_WEB_SERVICE)
                    {
                        Yii::app()->user->setFlash('notification',
                                yii::t('Default', 'The currency rate web service was unavailable. The rate could not be automatically updated.')
                        );
                        $currencyHelper->resetErrors();
                    }
                    $model->rateToBase = $rate;
                }
                if ($model->save())
                {
                    $this->redirectAfterSaveModel($model->id, $redirectUrlParams);
                }
            }
            return $model;
        }

        /**
         * Delete a currency as long as it is not in use.
         */
        public function actionDelete($id)
        {
            if (!CurrencyValue::isCurrencyInUseById(intval($id)))
            {
                $currency = Currency::GetById(intval($id));
                $currency->delete();
            }
            else
            {
                Yii::app()->user->setFlash('notification',
                        yii::t('Default', 'The currency was not removed because it is in use.')
                );
            }
            $this->redirect(array($this->getId() . '/configurationList'));
        }
    }
?>