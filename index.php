<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';
require 'class/Response.class.php';
require_once 'class/Shared.class.php';

header("Access-Control-Allow-Origin: *");

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

ini_set('memory_limit','256M');


//Manage logged in users
require 'lib/Facebook/facebook.php';
$facebook = new Facebook(array(
  'appId'  => '496720737093812',
  'secret' => '746494126d8ff117b27bbcf51b2aee01',
));

Shared::$Facebook = $facebook;

$authToken = null;
if(isset($_REQUEST['authToken'])){
    $authToken = $_REQUEST['authToken'];
}
$headers = getallheaders();
if(isset($headers['X-Facebook-Token'])){
    $authToken = $headers['X-Facebook-Token'];
}
$facebookUser = null;
$currentUser = null;
if(!empty($authToken)){
    try{
        $facebook->setAccessToken($authToken);
        $facebookUser = $facebook->api('/me');
    }
    catch(Exception $e){
        echo Response::toJSON(NULL,400,'Wrong OAuth token');
        die();
    }
    require_once 'class/User.class.php';
    $user = new User();
    $matchingUser = null;

    foreach($user->getList('fb_uid = "'.$facebookUser['id'].'"') as $_user){
        $user = $_user;
        break;
    }

    $user->firstname = $facebookUser['first_name'];
    $user->lastname = $facebookUser['last_name'];
    if(!empty($facebookUser['gender'])){
        $user->gender = $facebookUser['gender'];
    }
    $user->fb_uid = $facebookUser['id'];
    if(!empty($facebookUser['email'])){
        $user->email = $facebookUser['email'];
    }
    $user->save();

    $currentUser = $user;
}

/*API ROUTES*/

$app->get(
    '/',
    function () {
        echo Response::toJSON('Welcome to WeSwapp');
    }
);

$app->get('/favorites/', function(){
    global $currentUser;
    require_once 'class/Favorite.class.php';
    require_once 'class/User.class.php';
    require_once 'class/Item.class.php';

    if(!$currentUser){
        return print(Response::toJSON(NULL, 403));
    }
    $items = Favorite::getFavorites($currentUser->id);
    return print(Response::toJSON($items));
});

$app->post('/favorites/:id', function($id){
    global $currentUser;
    global $app;
    require_once 'class/Favorite.class.php';
    require_once 'class/User.class.php';
    require_once 'class/Item.class.php';
    if(!$currentUser){
        return print(Response::toJSON(NULL, 403));
    }
    $Item = new Item($id);
    if($Item->get('id') == null){
        return print(Response::toJSON(NULL, 404));
    }
    $body = json_decode($app->request->getBody(), true);
    $body = $body? $body : array();
    $data = array_merge($_REQUEST, $body);

    $active = intval(isset($data['active']) ? $data['active'] : 0);

    $Favorite = new Favorite(Favorite::getId($id,$currentUser->id));
    $Favorite->item_id = $id;
    $Favorite->user_id = $currentUser->id;
    $Favorite->is_active = $active?'1':'0';
    $Favorite->save();
    return print(Response::toJSON(Favorite::getFavorites($currentUser->id)));


});

$app->get('/contacts/', function(){
    global $currentUser;
    require_once 'class/Contact.class.php';
    require_once 'class/User.class.php';
    require_once 'class/Item.class.php';
    if(!$currentUser){
        return print(Response::toJSON(NULL, 403));
    }
    $Contact = new Contact();
    $contacts = array();
    $unread_count = 0;
    foreach ($Contact->getList('t.user_id = '.$currentUser->id.' OR t.item_id IN(SELECT i.id FROM items i WHERE i.user_id = '.$currentUser->id.') OR  t.requested_item_id IN(SELECT i.id FROM items i WHERE i.user_id = '.$currentUser->id.')', 'id', 'DESC', 150) as $contact) {
        $_contact = $contact->getData();
        $item_requested = new Item($contact->requested_item_id);
        $item_initiator = new Item($contact->item_id);
        $user_initiator = new User($item_initiator->user_id);

        $_contact['user_initiator'] = $user_initiator->getData();
        $_contact['item_requested'] = $item_requested->getAllData($currentUser->id);
        $_contact['item_initiator'] = $item_initiator->getAllData($currentUser->id);
        $_contact['isSent'] = $user_initiator->id == $currentUser->id;
        $_contact['isReceived'] = !$_contact['isSent'];

        $contacts[] = $_contact;

        $unread_count += ($_contact['isReceived'] && $_contact['is_seen'] == 0) ? 1 : 0;

    }
    $return = array(
        'contacts'=>$contacts,
        'unread_count'=>$unread_count
    );
    return print(Response::toJSON($return));
});

$app->post('/contacts/', function(){
    global $currentUser;
    global $app;

    require_once 'class/Contact.class.php';
    require_once 'class/User.class.php';
    require_once 'class/Item.class.php';

    if(!$currentUser){
        return print(Response::toJSON(NULL, 403));
    }

    $body = json_decode($app->request->getBody(), true);
    $body = $body? $body : array();
    $data = array_merge($_REQUEST, $body);

    if(empty($data['requested_item_id'])){
        return print(Response::toJSON(NULL,400,'Missing requested_item_id'));
    }
    $requested_item = new Item($data['requested_item_id']);
    if(!$requested_item->get('id')){
        return print(Response::toJSON(NULL,404));
    }

    if(empty($data['item_id'])){
        return print(Response::toJSON(NULL,400,'Missing item_id'));
    }

    $item = new Item($data['item_id']);
    if(!$item->get('id')){
        return print(Response::toJSON(NULL,404));
    }

    if($item->user_id != $currentUser->id){
        return print(Response::toJSON(NULL,400, 'You can\'t swap someone else\'s item'));
    }

    $Contact = new Contact();
    $Contact->item_id = $item->id;
    $Contact->user_id = $currentUser->id;
    $Contact->requested_item_id = $requested_item->id;
    $Contact->save();
    return print(Response::toJSON($Contact->getData()));

});

$app->post('/contacts/:id/', function($id){
    global $currentUser;
    global $app;
    require_once 'class/Item.class.php';
    require_once 'class/Contact.class.php';

    $body = json_decode($app->request->getBody(), true);
    $body = $body? $body : array();
    $data = array_merge($_REQUEST, $body);

    if(!$currentUser){
        return print(Response::toJSON(NULL, 403));
    }

    if(empty($data['status'])){
        return print(Response::toJSON(NULL,400,'Missing status'));

    }

    if(empty($id)){
        return print(Response::toJSON(NULL,400,'Missing contact_id'));
    }

    $Contact = new Contact($id);
    if($Contact->get('id') == null){
        return print(Response::toJSON(NULL,404));
    }
    $item = new Item($Contact->requested_item_id);
    if($item->user_id != $currentUser->id){
        return print(Response::toJSON(NULL,403));
    }

    $Contact->is_seen = 1;
    $Contact->status = $data['status'];
    $Contact->save();
    if($Contact->status == 'accepted'){
        $item2 = new Item($Contact->item_id);
        $item->status = 'unavailable';
        $item2->status = 'unavailable';
        $item->save();
        $item2->save(); 
    }
    return print(Response::toJSON($Contact->getData()));


});

$app->get('/users/:id', function($id){
    global $currentUser;
    require_once 'class/User.class.php';
    if($id == 'me'){
        $id = $currentUser->id;
    }
    $user = new User($id);
    if($user->get('id') == null){
        return print(Response::toJSON(NULL,404));
    }
    $_user = $user->getData();

    return print(Response::toJSON($_user));
});

$app->get('/users/:id/items', function($id){
    global $currentUser;
    require_once 'class/Item.class.php';
    if($id == 'me'){
        $id = $currentUser->id;
    }
    $item = new Item();
    $_items = array();
    foreach($item->getList('user_id='.intval($id)) as $_item){
        $_items[] = $_item->getAllData($id);
    }

    return print(Response::toJSON($_items));
});

$app->get('/items/:id', function($id){
    global $currentUser;

    require_once 'class/Item.class.php';
    require_once 'class/User.class.php';
    $item = new Item($id);
    if($item->get('id') == null){
        return print(Response::toJSON(NULL,404));
    }
    $id = null;
    if($currentUser){
        $id = $currentUser->id;
    }
    return print(Response::toJSON($item->getAllData()));
});

$app->get('/items/', function(){
    global $app;
    global $currentUser;
    require_once 'class/ItemListing.class.php';
    $body = json_decode($app->request->getBody(), true);
    $data = array_merge($_REQUEST, isset($body) ? $body : array());

    $id = $currentUser?$currentUser->id : null;
    $items = ItemListing::getItems(true, $id, $data);

    return print(Response::toJSON($items));
});

$app->post('/items/:id', function($id){
    global $app;
    global $currentUser;
    if(!$currentUser){
        return print(Response::toJSON(NULL,403));
    }
    require_once 'class/Item.class.php';
    require_once 'class/Media.class.php';
    require_once 'class/Tag.class.php';

    $item = new Item($id);
    if(!$item->get('id')){
        return print(Response::toJSON(NULL,404));
    }

    if($item->user_id != $currentUser->id){
        return print(Response::toJSON(NULL,403));
    }

    $body = json_decode($app->request->getBody(), true);
    $data = array_merge($_REQUEST, $body);

    if(isset($data['title']) && empty(trim($data['title']))){
        return print(Response::toJSON(null, 400, 'Missing title'));
    }
    if(isset($data['description']) && empty(trim($data['description']))){
        return print(Response::toJSON(null, 400, 'Missing description'));
    }
    if(isset($data['condition']) && empty(trim($data['condition']))){
        return print(Response::toJSON(null, 400, 'Missing condition'));
    }

    if(isset($data['medias']) && empty($data['medias'])){
        return print(Response::toJSON(null, 400, 'Missing medias array'));
    }

    $itemData = $item->getData();
    foreach($data as $k=>$v){
        if(isset($itemData[$k])){
            $item->$k=$v;
        }
    }

    if(isset($data['medias'])){ //Delete all medias, babe.
        $Media = new Media();
        foreach($Media->getList('item_id = '.intval($id)) as $media){
            $media->is_active = false;
            $media->save();
        }
    }

    foreach($data['medias'] as $media){
        $Media = new Media();
        $Media->item_id = $id;
        $Media->processPicture($media);
        $Media->save();
    }
    Tag::removeTags($item->id);
    Tag::addTags($item->id, $item->getTags());

    return print(Response::toJSON());

});


$app->post(
    '/items',
    function () {
        global $app;
        global $currentUser;

        if(!$currentUser){
            return print(Response::toJSON(null, 403));
        }

        $body = json_decode($app->request->getBody(), true);
        $body = $body? $body : array();
        $data = array_merge($_REQUEST, $body);


        $defaultData = array(
            'title'=>NULL,
            'description'=>NULL,
            'status'=>'available',
            'condition' => 'used',
            'latitude' => NULL,
            'longitude' => NULL,
        );
        foreach($defaultData as $k=>$v){
            if(!isset($data[$k])){
                $data[$k] = $defaultData[$k];
            }
        }

        if(empty($data['medias']) && !empty($data['media'])){
            $data['medias'] = array(&$data['media']);
        }

        if(empty(trim($data['title']))){
            return print(Response::toJSON(null, 400, 'Missing title'));
        }
        if(empty(trim($data['description']))){
            return print(Response::toJSON(null, 400, 'Missing description'));
        }
        if(empty(trim($data['condition']))){
            return print(Response::toJSON(null, 400, 'Missing condition'));
        }

        if(empty($data['medias'])){
            return print(Response::toJSON(null, 400, 'Missing medias array'));
        }

        require_once 'class/Item.class.php';
        require_once 'class/Media.class.php';
        require_once 'class/Tag.class.php';

        $Item = new Item();
        $Item->description = $data['description'];
        $Item->title = $data['title'];
        $Item->latitude = $data['latitude'];
        $Item->longitude = $data['longitude'];
        $Item->condition = $data['condition'];
        $Item->status = $data['status'];
        $Item->user_id = $currentUser->id;
        $Item->save();

        foreach($data['medias'] as $media){
            $Media = new Media();
            $Media->item_id = $Item->id;
            $Media->processPicture($media);
            $Media->save();
        }
        
        Tag::addTags($item->id, $item->getTags());

        return print(Response::toJSON($Item->getAllData($currentUser->id)));

    }
);

$app->notFound(function () use ($app) {
    return print(Response::toJSON('This API call does not exist', 404));
});

// PUT route
$app->put(
    '/put',
    function () {
        echo 'This is a PUT route';
    }
);

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete(
    '/delete',
    function () {
        echo 'This is a DELETE route';
    }
);

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
