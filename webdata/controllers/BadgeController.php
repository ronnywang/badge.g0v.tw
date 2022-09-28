<?php

class BadgeController extends Pix_Controller
{
    public function showAction()
    {
        if (!$service = Service::find(intval($_GET['service_id']))) {
            return $this->redirect('/');
        }
        if (!$badge = ServiceBadge::search(['service_id' => $service->id, 'badge_hash' => intval($_GET['hash'])])->first()) {
            return $this->redirect('/');
        }
        $this->view->service = $service;
        $this->view->badge = $badge;
    }
}

