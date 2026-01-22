<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Controllers;

use TNotifyer\Engine\Storage;

/**
 * 
 * OZON controller.
 * 
 */
class OZONController extends AbstractWebController {

    /**
     * Get info handler.
     * 
     * @return Response current response
     */
    public function info()
    {
        // response
        return $this->response->print([
            'Seller Info' => Storage::get('OZON')->getInfo(),
            'Roles' => Storage::get('OZON')->getRoles()
        ]);
    }

    /**
     * Do check handler.
     * 
     * @return Response current response
     */
    public function doCheck()
    {
        // make an action
        Storage::get('OZON')->doCheck();

        // response
        return $this->response->text('Check done.');
    }

    /**
     * Do test handler.
     * 
     * @return Response current response
     */
    public function doTest()
    {
        // get request optional parameter
        $period = $this->request->getParam('period', '7 days');

        // response
        return $this->response->print(Storage::get('OZON')->makeFBSListTest($period));
    }
}