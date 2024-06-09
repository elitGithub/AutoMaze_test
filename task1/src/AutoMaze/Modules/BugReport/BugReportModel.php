<?php

namespace AutoMaze\Modules\BugReport;

use Core\Model;
use Core\Request;
use Core\Storm;

class BugReportModel extends Model
{
    protected string $tableName = 'bug_reports';
    public array     $rules     = [
        'title'   => [self::RULE_REQUIRED],
        'urgency' => [self::RULE_REQUIRED],
    ];

    public function params(): array
    {
        return $this->params;
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function updateBug(int $id, string $status)
    {
        $query = "UPDATE `$this->tableName` SET `status` = ? WHERE `id` = ? RETURNING *";
        $res = Storm::getStorm()->db->pquery($query, [$status, $id]);
        $affectedRows = Storm::getStorm()->db->num_rows($res);

        if ($affectedRows > 0) {
            $bug = $this->getBugById($id);
            $data = [
                'status' => $status,
                'bugInfo' => $bug,
            ];
            Storm::getStorm()->emitEvent('bugStatusUpdate', $data);
        }

        return $affectedRows;
    }

    public function getBugById(int $id)
    {
        $query = "SELECT * FROM `$this->tableName` WHERE `id` = ?;";
        $res = Storm::getStorm()->db->pquery($query, [$id]);
        return Storm::getStorm()->db->fetch_array($res);
    }


    public function getBugs(): array
    {
        $query = "SELECT * FROM `$this->tableName`;";
        $res = Storm::getStorm()->db->query($query);
        $bugs = [];
        while ($row = Storm::getStorm()->db->fetchByAssoc($res)) {
            $bugs[] = $row;
        }
        return $bugs;
    }

    public function reportABug(Request $request)
    {
        $tables = Storm::getStorm()->db->get_tables();
        if (!in_array($this->tableName, $tables)) {
            $this->createBugsTable();
        }
        $this->loadAttributes();
        foreach ($request as $name => $value) {
            if (isset($this->attributes[$name])) {
                $this->attributes[$name] = $value;
            }
        }
        if ($this->validate()) {
            extract($this->attributes);
            $query = "INSERT INTO $this->tableName (`submitted_by`, `title`, `urgency`, `status`) VALUES (?, ?, ?, 'new')";
            Storm::getStorm()->db->pquery($query, [$submitted_by, $title, $urgency]);
            $id = Storm::getStorm()->db->getLastInsertID();
            $commentsModule = Storm::getStorm()->getModuleInstance('comments');
            $commentsModule->getModel()->addCommentToBug($id, $request['comment'], $submitted_by);
            return $this->module->getController()->renderComponent('success-message');
        }
        return $this->module->getController()->renderComponent('error-message');
    }

    public function getDisplayName(): string
    {
        return 'Bug Reports';
    }

    private function createBugsTable()
    {
        Storm::getStorm()->db->query('DROP TABLE IF EXISTS ' . $this->tableName);
        $query = 'CREATE TABLE `bug_reports` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `submitted_by` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `urgency` VARCHAR(255) NOT NULL,
    `status` VARCHAR(255) NOT NULL);
CREATE INDEX `idx_urgency` ON `bug_reports` (`urgency`);
CREATE INDEX `idx_submitted_by` ON `bug_reports` (`submitted_by`);
CREATE INDEX `idx_status` ON `bug_reports` (`status`);
';
        Storm::getStorm()->db->query($query);
    }
}