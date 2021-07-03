<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\errors\ElementNotFoundException;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use Throwable;
use yii\base\Exception;

class Fragments extends Component
{
    public function getFragmentById($id)
    {
        $result = $this->_createFragmentsQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new Fragment($result) : null;
    }

    /**
     * @throws Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public function saveFragment(Fragment $fragment)
    {
        Craft::$app->elements->saveElement($fragment);
    }

    private function _createFragmentsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid',
                'fieldLayoutId',
            ])
            ->from([Table::FRAGMENTS]);
    }
}
