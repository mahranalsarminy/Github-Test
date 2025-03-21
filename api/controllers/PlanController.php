<?php
require_once __DIR__ . '/../models/SubscriptionPlan.php';

function get_subscription_plans() {
    $plan = new SubscriptionPlan();
    $plans = $plan->get_all();
    echo json_encode(['data' => $plans]);
}
?>