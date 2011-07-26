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

    class AccountTest extends BaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            SecurityTestHelper::createUsers();
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        public function testCreateAndGetAccountById()
        {
            $user = UserTestHelper::createBasicUser('Steven');

            $account = new Account();
            $account->owner       = $user;
            $account->name        = 'Test Account';
            $account->officePhone = '1234567890';
            $this->assertTrue($account->save());
            $id = $account->id;
            unset($account);
            $account = Account::getById($id);
            $this->assertEquals('Test Account', $account->name);
            $this->assertEquals('1234567890',   $account->officePhone);
        }

        /**
         * This test can be used by any frozen running test to test out boolean values in the database, that they
         * save and change correctly.
         * @depends testCreateAndGetAccountById
         */
        public function testEmailBooleanValues()
        {
            $accounts = Account::getByName('Test Account');
            $this->assertEquals(1, count($accounts));
            $account = $accounts[0];

            $email = new Email();
            $email->optOut = 1;
            $email->emailAddress = 'a@a.com';
            $account->primaryEmail = $email;
            $email2 = new Email();
            $email2->optOut = 0;
            $email2->emailAddress = 'a@b.com';
            $account->secondaryEmail = $email2;
            $this->assertTrue($account->save());
            $id = $account->id;
            unset($account);

            $account = Account::getById($id);
            $this->assertEquals   (1,     $account->primaryEmail->optOut);
            $this->assertNotEquals(true,  $account->primaryEmail->optOut);
            $this->assertEquals   (0,     $account->secondaryEmail->optOut);
            $this->assertNotEquals(false, $account->secondaryEmail->optOut);

            $account->primaryEmail->optOut = 0;
            $this->assertTrue($account->save());
            $id = $email->id;
            unset($email);

            $account = Account::getById($id);
            $this->assertEquals   (0,     $account->primaryEmail->optOut);
            $this->assertNotEquals(false, $account->primaryEmail->optOut);

            $account->primaryEmail->optOut = 3;
            $this->assertFalse($account->save());
            //forget account so optOut value 3 doesnt get cached.
            $account->forget();
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testGetAccountsByName()
        {
            $accounts = Account::getByName('Test Account');
            $this->assertEquals(1, count($accounts));
            $this->assertEquals('Test Account', $accounts[0]->name);
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testGetLabel()
        {
            $accounts = Account::getByName('Test Account');
            $this->assertEquals(1, count($accounts));
            $this->assertEquals('Account',  $accounts[0]::getModelLabelByTypeAndLanguage('Singular'));
            $this->assertEquals('Accounts', $accounts[0]::getModelLabelByTypeAndLanguage('Plural'));
        }

        /**
         * @depends testGetAccountsByName
         */
        public function testGetAccountsByNameForNonExistentName()
        {
            $accounts = Account::getByName('Test Account 69');
            $this->assertEquals(0, count($accounts));
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testGetAll()
        {
            $user = User::getByUsername('steven');

            $account = new Account();
            $account->owner = $user;
            $account->name  = 'Test Account 2';
            $this->assertTrue($account->save());
            $accounts = Account::getAll();
            $this->assertEquals(2, count($accounts));
            $this->assertTrue('Test Account'   == $accounts[0]->name &&
                              'Test Account 2' == $accounts[1]->name ||
                              'Test Account 2' == $accounts[0]->name &&
                              'Test Account'   == $accounts[1]->name);
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testSetAndGetOwner()
        {
            $user = UserTestHelper::createBasicUser('Dicky');

            $accounts = Account::getByName('Test Account');
            $this->assertEquals(1, count($accounts));
            $account = $accounts[0];
            $account->owner = $user;
            $saved = $account->save();
            $this->assertTrue($saved);
            unset($user);
            $this->assertTrue($account->owner->id > 0);
            $user = $account->owner;
            $account->owner = null;
            $this->assertNotNull($account->owner);
            $this->assertFalse($account->validate());
            $account->forget();
        }

        /**
         * @depends testSetAndGetOwner
         */
        public function testReplaceOwner()
        {
            $accounts = Account::getByName('Test Account');
            $this->assertEquals(1, count($accounts));
            $account = $accounts[0];
            $user = User::getByUsername('dicky');
            $this->assertEquals($user->id, $account->owner->id);
            unset($user);
            $account->owner = User::getByUsername('benny');
            $this->assertTrue($account->owner !== null);
            $user = $account->owner;
            $this->assertEquals('benny', $user->username);
            unset($user);
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testUpdateAccountFromForm()
        {
            $accounts = Account::getByName('Test Account');
            $account = $accounts[0];
            $this->assertEquals($account->name, 'Test Account');
            $postData = array('name' => 'New Name');
            $account->setAttributes($postData);
            $this->assertTrue($account->save());

            $id = $account->id;
            unset($account);
            $account = Account::getById($id);
            $this->assertEquals('New Name', $account->name);
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testEmailAndAddresses()
        {
            $accounts = Account::getAll();
            $this->assertTrue(count($accounts) > 0);
            $account = $accounts[0];
            $account->primaryEmail->emailAddress   = 'thejman@zurmoinc.com';
            $account->primaryEmail->optOut         = 0;
            $account->primaryEmail->isInvalid      = 0;
            $account->secondaryEmail->emailAddress = 'digi@magic.net';
            $account->secondaryEmail->optOut       = 1;
            $account->secondaryEmail->isInvalid    = 1;
            $account->billingAddress->street1      = '129 Noodle Boulevard';
            $account->billingAddress->street2      = 'Apartment 6000A';
            $account->billingAddress->city         = 'Noodleville';
            $account->billingAddress->postalCode   = '23453';
            $account->billingAddress->country      = 'The Good Old US of A';
            $account->shippingAddress->street1     = '25 de Agosto 2543';
            $account->shippingAddress->street2     = 'Local 3';
            $account->shippingAddress->city        = 'Ciudad de Los Fideos';
            $account->shippingAddress->postalCode  = '5123-4';
            $account->shippingAddress->country     = 'Latinoland';
            $this->assertTrue($account->save(false));
            $id = $account->id;
            unset($account);
            $account = Account::getById($id);
            $this->assertEquals('thejman@zurmoinc.com', $account->primaryEmail->emailAddress);
            $this->assertEquals(0,                          $account->primaryEmail->optOut);
            $this->assertEquals(0,                          $account->primaryEmail->isInvalid);
            $this->assertEquals('digi@magic.net',           $account->secondaryEmail->emailAddress);
            $this->assertEquals(1,                          $account->secondaryEmail->optOut);
            $this->assertEquals(1,                          $account->secondaryEmail->isInvalid);
            $this->assertEquals('129 Noodle Boulevard',     $account->billingAddress->street1);
            $this->assertEquals('Apartment 6000A',          $account->billingAddress->street2);
            $this->assertEquals('Noodleville',              $account->billingAddress->city);
            $this->assertEquals('23453',                    $account->billingAddress->postalCode);
            $this->assertEquals('The Good Old US of A',     $account->billingAddress->country);
            $this->assertEquals('25 de Agosto 2543',        $account->shippingAddress->street1);
            $this->assertEquals('Local 3',                  $account->shippingAddress->street2);
            $this->assertEquals('Ciudad de Los Fideos',     $account->shippingAddress->city);
            $this->assertEquals('5123-4',                   $account->shippingAddress->postalCode);
            $this->assertEquals('Latinoland',               $account->shippingAddress->country);
        }

        /**
         * @depends testEmailAndAddresses
         */
        public function testDeleteAccount()
        {
            $accounts = Account::getAll();
            $this->assertEquals(2, count($accounts));
            $accounts[0]->delete();
            $accounts = Account::getAll();
            $this->assertEquals(1, count($accounts));
            $accounts[0]->delete();
            $accounts = Account::getAll();
            $this->assertEquals(0, count($accounts));
        }

        /**
         * @depends testEmailAndAddresses
         */
        public function testGetAllWhenThereAreNone()
        {
            $accounts = Account::getAll();
            $this->assertEquals(0, count($accounts));
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testSetIndustryAndRetrieveDisplayName()
        {
            $user = User::getByUsername('steven');

            $values = array(
                'Automotive',
                'Adult Entertainment',
                'Financial Services',
                'Mercenaries & Armaments',
            );
            $industryFieldData = CustomFieldData::getByName('Industries');
            $industryFieldData->serializedData = serialize($values);
            $this->assertTrue($industryFieldData->save());

            $account = new Account();
            $account->owner = $user;
            $account->name = 'Jim\'s Software Company';
            $account->industry->value = $values[1];
            //$account->data            = $industryFieldData;
            $this->assertTrue($account->save());
            $this->assertTrue($account->id !== null);
            $id = $account->id;
            unset($account);
            $account = Account::getById($id);
            $this->assertEquals('Adult Entertainment', $account->industry->value);

            //Confirm a new account with no defaults set, will not show a default industry value.
            $account = new Account(false);
            $this->assertNull($account->industry->value);
        }

        public function testIndustryWithDefaultValueDoesntDefaultWhenAccountSetDefaultsFalse()
        {
            $values = array(
                'Automotive',
                'Adult Entertainment',
                'Financial Services',
                'Mercenaries & Armaments',
            );
            $industryFieldData = CustomFieldData::getByName('Industries');
            $industryFieldData->serializedData = serialize($values);
            $industryFieldData->defaultValue = $values[2];
            $this->assertTrue($industryFieldData->save());
            $account = new Account();
            $this->assertEquals($values[2], $account->industry->value);
            //Set first parameter to false, and confirm the value is null for industry
            $account = new Account(false);
            $this->assertNull($account->industry->value);
        }

        /**
         * @depends testReplaceOwner
         */
        public function testSetAttributeWithEmptyValue()
        {
            $user = User::getByUsername('benny');
            $this->assertEquals('benny', $user->username);
            $account = new Account();
            $account->name = 'abc';
            $account->owner = $user;
            $this->assertTrue($account->save());
            $account = Account::getById($account->id);
            $this->assertEquals('abc', $account->name);
            $fakePostData = array(
                'name' => '',
            );
            $account->setAttributes($fakePostData);
            $this->assertEquals('', $account->name);
        }

        public function testOwnerNotPopulatedWhenNoDefaults()
        {
            $account = new Account();
            $this->assertEquals('super', $account->owner->username);
            $account->validate();
            $account = new Account(false);
            $this->assertEquals('', $account->owner->username);
            $account->validate();
        }

        /**
         * @depends testCreateAndGetAccountById
         */
        public function testValidatesWithoutOwnerWhenSpecifyingAttributesToValidate()
        {
            $user = User::getByUsername('steven');
            $this->assertTrue($user->id > 0);
            $account = new Account(false);
            $_POST['MassEdit'] = array(
                'employees' => '1',
            );
            $_POST['fake'] = array(
                'employees' => 4,
            );
            PostUtil::sanitizePostForSavingMassEdit('fake');
            $account->setAttributes($_POST['fake']);
            $account->validate(array_keys($_POST['MassEdit']));
            $this->assertEquals(array(), $account->getErrors());
            $account->forget();
            $account = new Account(false);
            $_POST['MassEdit'] = array(
                'owner' => '1',
            );
            $_POST['fake']  = array(
                'owner'     => array(
                    'id'    => '',
                )
            );
            PostUtil::sanitizePostForSavingMassEdit('fake');
            $account->setAttributes($_POST['fake']);
            $account->validate(array_keys($_POST['MassEdit']));
            //there should be an owner error since it is specified but blank
            $this->assertNotEquals(array(), $account->getErrors());
            $account->forget();
            $account = new Account(false);
            $_POST['MassEdit'] = array(
                'employees' => '1',
                'owner'     => '2',
            );
            $_POST['fake'] = array(
                'employees' => 4,
                'owner'     => array(
                     'id' => $user->id,
                )
            );
            PostUtil::sanitizePostForSavingMassEdit('fake');
            $account->setAttributes($_POST['fake']);
            $account->validate(array_keys($_POST['MassEdit']));
            $this->assertEquals(array(), $account->getErrors());
        }

        public function testSettingDefaultValueForType()
        {
            $values = array(
                'Prospect',
                'Customer',
                'Vendor',
            );
            $typeFieldData = CustomFieldData::getByName('AccountTypes');
            $typeFieldData->serializedData = serialize($values);
            $this->assertTrue($typeFieldData->save());

            //Add default value to type attribute for account.
            $attributeForm = new DropDownAttributeForm();
            $attributeForm->attributeName       = 'type';
            $attributeForm->attributeLabels  = array(
                'de' => 'Type',
                'en' => 'Type',
                'es' => 'Type',
                'fr' => 'Type',
                'it' => 'Type',
            );
            $attributeForm->isAudited           = true;
            $attributeForm->isRequired          = true;
            $attributeForm->defaultValueOrder   = 2;
            $attributeForm->customFieldDataData = $values;
            $attributeForm->customFieldDataName = 'AccountTypes';

            $modelAttributesAdapterClassName = $attributeForm::getModelAttributeAdapterNameForSavingAttributeFormData();
            $adapter = new $modelAttributesAdapterClassName(new Account());
            try
            {
                $adapter->setAttributeMetadataFromForm($attributeForm);
            }
            catch(FailedDatabaseSchemaChangeException $e)
            {
                echo $e->getMessage();
                $this->fail();
            }

            $model = new Account();
            $this->assertEquals($values[2], $model->type->value);

            $user = User::getByUsername('billy');

            $_FAKEPOST = array(
                'name' => 'aTestAccount',
                'type' => array(
                    'value' => $values[1],
                ),
                'owner'     => array(
                     'id' => $user->id,
                )
            );

            $model->setAttributes($_FAKEPOST);
            $this->assertEquals($values[1], $model->type->value);
            $this->assertTrue($model->save());
        }

        public function testMemberOfMembersRelation()
        {
            $user = User::getByUsername('billy');
            $hq      = AccountTestHelper::createAccountByNameForOwner('Headquarters', $user);
            $branch1 = AccountTestHelper::createAccountByNameForOwner('Branch 1',     $user);
            $branch2 = AccountTestHelper::createAccountByNameForOwner('Branch 2',     $user);
            $branch3 = AccountTestHelper::createAccountByNameForOwner('Branch 3',     $user);
            $branch4 = AccountTestHelper::createAccountByNameForOwner('Branch 4',     $user);
            //Connect branches to headquarters.
            $hq->members->add($branch1);
            $hq->members->add($branch2);
            $hq->members->add($branch3);
            $hq->members->add($branch4);
            $this->assertTrue($hq->save());
            //Now add 2 sub branches for branch2. 2a and 2b
            $branch2a = AccountTestHelper::createAccountByNameForOwner('Branch 2a', $user);
            $branch2b = AccountTestHelper::createAccountByNameForOwner('Branch 2b', $user);
            $branch2->members->add($branch2a);
            $branch2->members->add($branch2b);
            $this->assertTrue($branch2->save());
            //Make sure hq shows branches 1 - 4 as members.
            $this->assertEquals(4, $hq->members->count());
            for ($i = 0; $i < 4; $i++)
            {
                $branchNumber = $i + 1;
                $this->assertEquals("Branch $branchNumber", $hq->members[$i]->name);
                $this->assertEquals("Headquarters",         $hq->members[$i]->memberOf->name);
                $this->assertTrue  ($hq->isSame(            $hq->members[$i]->memberOf));
                $this->assertTrue  ($hq ===                 $hq->members[$i]->memberOf);
            }
        }

        /**
         * This test was originally here because we did not want the value of the phoneOffice when null to be coming
         * back as 0. But this is how redBean works to stay consistent with all dbs.
         * @see http://groups.google.com/group/redbeanorm/browse_thread/thread/e6a0a9d29838d973/90d12a0146544a0b
         * So for now, we will adjust the phone element in the user interface.
         */
        public function testOfficePhoneSetsToZeroWhenClearingAndForgetting()
        {
            $user = User::getByUsername('steven');

            $account = new Account();
            $account->owner       = $user;
            $account->name        = 'Test Account2';
            $account->officePhone = '1234567890';
            $this->assertTrue($account->save());
            $id = $account->id;
            unset($account);
            $account = Account::getById($id);
            $this->assertEquals('Test Account2', $account->name);
            $this->assertEquals('1234567890',   $account->officePhone);

            $account->setAttributes(array('officePhone' => ''));
            $this->assertTrue($account->save());
            $account->forget();
            $account = Account::getById($id);
            $this->assertEquals(0, $account->officePhone);
        }

        public function testGetModelClassNames()
        {
            $modelClassNames = AccountsModule::getModelClassNames();
            $this->assertEquals(2, count($modelClassNames));
            $this->assertEquals('Account', $modelClassNames[0]);
            $this->assertEquals('AccountsFilteredList', $modelClassNames[1]);
        }
    }
?>