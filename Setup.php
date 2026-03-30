<?php

namespace MySolutions;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\AddOn\StepRunnerUninstallTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $fieldId = 'solution_visibility';

        /** @var \XF\Entity\UserField $existingField */
        $existingField = $this->app->em()->find('XF:UserField', $fieldId);
        if ($existingField) {
            return;
        }

        /** @var \XF\Entity\UserField $field */
        $field = $this->app->em()->create('XF:UserField');
        $field->field_id = $fieldId;
        $field->display_group = 'preferences'; // Раздел "Настройки"
        $field->display_order = 1; // Порядок отображения
        $field->field_type = 'select'; // Выпадающий список или радио-кнопки
        $field->match_type = 'none';
        $field->required = false;
        $field->moderator_editable = true; // Могут ли редактировать модераторы?
        $field->user_editable = 'yes'; // Может ли редактировать пользователь?
        $field->viewable_profile = false; // Будет ли отображаться поле в профиле?

        $field->field_choices = [
            'everyone' => 'Все посетители',
            'followed' => 'Те, на кого я подписан(-а)',
            'none' => 'Никто, кроме меня'
        ];

        $field->save();
    }

    public function uninstallStep1()
    {
        $fieldId = 'solution_visibility';

        /** @var \XF\Entity\UserField $field */
        $field = $this->app->em()->find('XF:UserField', $fieldId);
        if ($field) {
            $field->delete();
        }
    }

    public function uninstallStep2()
    {
        $this->db()->delete('xf_user_field_value', 'field_id = ?', 'solution_visibility');
    }
}
