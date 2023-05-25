<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ImageModel;
use App\Models\ItemModel;
use CodeIgniter\API\ResponseTrait;
use Config\Services;

class Item extends BaseController
{
    use ResponseTrait;

    protected String $userId;

    public function __construct()
    {
        $this->userId = session()->getFlashdata('user_id');
    }

    public function index()
    {
        $db = new ItemModel;
        $items = $db->where(['user_id' => $this->userId])->findAll();

        if (!$items) {
            return $this->failNotFound('Items not found');
        }

        $imageDb = new ImageModel;

        foreach ($items as $key1 => $value1) {
            $imageArray = $imageDb->where(['item_id' => $value1['item_id']])->findAll();

            if ($imageArray) {
                foreach ($imageArray as $key2 => $value2) {
                    $imageArray[$key2]['image'] = Services::fullImageURL($value2['image']);
                }
            }

            $items[$key1]['images'] = $imageArray != [] ? $imageArray : null;
        }

        return $this->respond([
            'status' => 200,
            'messages' => ['success' => 'OK'],
            'data' => $items,
        ]);
    }

    public function show($id = null)
    {
        $db = new ItemModel;
        $item = $db->where(['item_id' => $id, 'user_id' => $this->userId])->first();

        if (!$item) {
            return $this->failNotFound('Item not found');
        }

        $imageDb = new ImageModel;

        $imageArray = $imageDb->where(['item_id' => $item['item_id']])->findAll();

        if ($imageArray) {
            foreach ($imageArray as $key => $value) {
                $imageArray[$key]['image'] = Services::fullImageURL($value['image']);
            }
        }

        $item['images'] = $imageArray != [] ? $imageArray : null;

        return $this->respond([
            'status' => 200,
            'messages' => ['success' => 'OK'],
            'data' => $item,
        ]);
    }

    public function create()
    {
        if (!$this->validate([
            // 'user_id'       => 'required|numeric',
            'item_name'     => 'required',
            'description'   => 'required',
            'initial_price' => 'required|numeric',
        ])) {
            return $this->failValidationErrors(\Config\Services::validation()->getErrors());
        }

        $insert = [
            // 'user_id'       => $this->request->getVar('user_id'),
            'user_id'       => $this->userId,
            'item_name'     => $this->request->getVar('item_name'),
            'description'   => $this->request->getVar('description'),
            'initial_price' => $this->request->getVar('initial_price'),
        ];

        $db = new ItemModel;
        $save  = $db->insert($insert);

        if (!$save) {
            return $this->failServerError(description: 'Failed to create item');
        }

        return $this->respondCreated([
            'status' => 200,
            'messages' => ['success' => 'OK']
        ]);
    }

    public function update($id = null)
    {
        if (!$this->validate([
            'user_id'       => 'permit_empty|numeric',
            'item_name'     => 'permit_empty',
            'description'   => 'permit_empty',
            'initial_price' => 'permit_empty|numeric',
        ])) {
            return $this->failValidationErrors(\Config\Services::validation()->getErrors());
        }

        $db = new ItemModel;
        $exist = $db->where(['item_id' => $id, 'user_id' => $this->userId])->first();

        if (!$exist) {
            return $this->failNotFound(description: 'Item not found');
        }

        $update = [
            // 'user_id' => $this->request->getRawInputVar('user_id')
            //     ?? $exist['userId'],
            'item_name' => $this->request->getRawInputVar('item_name')
                ?? $exist['item_name'],
            'description' => $this->request->getRawInputVar('description')
                ?? $exist['description'],
            'initial_price' => $this->request->getRawInputVar('initial_price')
                ?? $exist['initial_price']
        ];

        $save = $db->update($id, $update);

        if (!$save) {
            return $this->failServerError(description: 'Failed to update item');
        }

        return $this->respondUpdated([
            'status' => 200,
            'messages' => [
                'success' => 'Item updated successfully'
            ]
        ]);
    }

    public function delete($id = null)
    {
        $db = new ItemModel;
        $exist = $db->where(['item_id' => $id, 'user_id' => $this->userId])->first();

        if (!$exist) return $this->failNotFound(description: 'Item not found');

        $delete = $db->delete($id);

        if (!$delete) return $this->failServerError(description: 'Failed to delete item');

        return $this->respondDeleted([
            'status' => 200,
            'messages' => ['success' => 'Item successfully deleted']
        ]);
    }
}
