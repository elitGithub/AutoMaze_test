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

    public function getBugs()
    {
        $query = "SELECT * FROM $this->tableName";
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
            $query = "INSERT INTO $this->tableName (`submitted_by`, `title`, `urgency`, `comment_id`, `status`) VALUES (?, ?, ?, 0, 'new')";
            Storm::getStorm()->db->pquery($query, [$submitted_by, $title, $urgency]);
            $id = Storm::getStorm()->db->getLastInsertID();
            $commentsModule = Storm::getStorm()->getModuleInstance('comments');
            $commentsModule->getModel()->addCommentToBug($id, $request['comment'], $submitted_by);
            return "<div id='flashMessage' class='text-green-500 px-4 py-3 rounded bg-green-200' hx-get='/home' hx-trigger='load delay:3s' hx-swap='outerHTML'>Bug report submitted successfully!</div>";
        }
        return "<div id='flashMessage' class='text-red-500 px-4 py-3 rounded bg-red-200' hx-get='/home' hx-trigger='load delay:3s' hx-swap='outerHTML'>Failed to submit a bug report. Please try again.</div>";
    }

    public function getDisplayName(): string
    {
        return 'Bug Reports';
    }

    private function createBugsTable()
    {
        $query = 'CREATE TABLE `bug_reports` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    `submitted_by` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `urgency` VARCHAR(255) NOT NULL,
    `comment_id` VARCHAR(255) NOT NULL,
    `status` VARCHAR(255) NOT NULL,
     FOREIGN KEY (`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE);
CREATE INDEX `idx_urgency` ON `bug_reports` (`urgency`);
CREATE INDEX `idx_submitted_by` ON `bug_reports` (`submitted_by`);
CREATE INDEX `idx_status` ON `bug_reports` (`status`);
';
        Storm::getStorm()->db->query($query);
    }
}