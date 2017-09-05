<?php
/*
#######################################################
#### Name: goAssignedAgents.php	                   ####
#### Description: API to get all assigned agents   ####
#### Version: 0.9                                  ####
#### Copyright: GOAutoDial Inc. (c) 2011-2014      ####
#### Written by: Chris Lomuntad                    ####
#### License: AGPLv2                               ####
#######################################################
*/

if (isset($_GET['campaigns'])) { $campaigns = $_GET['campaigns']; }
    else if (isset($_POST['campaigns'])) { $campaigns = $_POST['campaigns']; }
if (isset($_GET['agents'])) { $agents = $_GET['agents']; }
    else if (isset($_POST['agents'])) { $agents = $_POST['agents']; }
if (isset($_GET['location'])) { $location = $_GET['location']; }
    else if (isset($_POST['location'])) { $location = $_POST['location']; }

$newAssignments = [];
foreach ($agents as $agent) {
	$astDB->where('user_id', $agent);
	$result = $astDB->getOne('vicidial_users', 'user');
	$user = $result['user'];
	foreach ($campaigns as $campaign) {
		$astDB->where('user', $user);
		$astDB->where('campaign_id', $campaign);
		$rslt = $astDB->get('vicidial_campaign_agents');
		$existingCampaignUser = $astDB->getRowCount();
		if($existingCampaignUser < 1) {
			$insertData = array(
				'user' => $user,
				'campaign_id' => $campaign,
				'campaign_rank' => 0,
				'campaign_weight' => 0,
				'calls_today' => 0,
				'group_web_vars' => '',
				'campaign_grade' => 1
			);
			$astDB->insert('vicidial_campaign_agents', $insertData);
			
			$astDB->where('vca.user', $user);
			$astDB->where('vca.campaign_id', $campaign);
			$astDB->join('vicidial_campaigns vc', 'vca.campaign_id=vc.campaign_id', 'INNER');
			$astDB->join('vicidial_users vu', 'vu.user=vca.user', 'INNER');
			$result = $astDB->get('vicidial_campaign_agents vca', null, 'vca.id AS campaign_agent, vu.user_id, vu.user, vu.full_name, vc.campaign_name,vc.campaign_id');
			
			$newCampaignAgent = $result[0];
			$newCampaignAgent['campaign_name'] = html_entity_decode($newCampaignAgent['campaign_name']);
			$newAssignments[] = $newCampaignAgent;
	   }
	}
	
	//Assign Location
	$goDB->where('name', $location);
	$rslt = $goDB->getOne('locations', 'id');
	$location_id = $rslt['id'];
	
	$goDB->where('userid', $agent);
	$goDB->update('users', array('location_id' => $location_id));
}

$APIResult = array("result" => "success", "data" => $newAssignments);
?>
