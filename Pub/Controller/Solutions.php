<?php

namespace MySolutions\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Solutions extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $this->assertRegistrationRequired();

        $userId = $this->filter('user_id', 'uint');
        if (!$userId) {
            return $this->notFound();
        }

        /** @var \XF\Entity\User $profileUser */
        $profileUser = $this->assertRecordExists('XF:User', $userId);

        if (!$this->canViewSolutions($profileUser)) {
            return $this->noPermission();
        }

        $page = $this->filterPage();
        $perPage = 20;
        $order = $this->filter('order', 'str', 'desc');
        $orderDir = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        $finder = $this->finder('XF:ThreadQuestion')
            ->with('Solution', true)
            ->with('Thread', true)
            ->with('Thread.User')
            ->where('Solution.user_id', $userId)
            ->order('Thread.last_post_date', $orderDir)
            ->limitByPage($page, $perPage);

        $total = $finder->total();
        $solutions = $finder->fetch();

        $viewParams = [
            'solutions' => $solutions,
            'profileUser' => $profileUser,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'order' => strtolower($order),
        ];

        return $this->view('MySolutions:Solutions\List', 'mysolutions_list', $viewParams);
    }

    protected function canViewSolutions(\XF\Entity\User $profileUser): bool
    {
        $visitor = \XF::visitor();
        $customFields = $profileUser->Profile->custom_fields;

        $visibility = 'everyone';
        if ($customFields->offsetExists('solution_visibility')) {
            $visibility = $customFields['solution_visibility'];
            if (is_array($visibility)) {
                $visibility = reset($visibility);
            }
        }

        switch ($visibility) {
            case 'none':
                return $profileUser->user_id === $visitor->user_id
                    || $visitor->is_moderator
                    || $visitor->is_admin;

            case 'followed':
                if (!$visitor->user_id) {
                    return false;
                }
                if ($profileUser->user_id === $visitor->user_id) {
                    return true;
                }
                return (bool) $this->finder('XF:UserFollow')
                    ->where('user_id', $profileUser->user_id)
                    ->where('follow_user_id', $visitor->user_id)
                    ->fetchOne();

            default:
                return true;
        }
    }
}
