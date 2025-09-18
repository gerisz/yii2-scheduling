<?php

namespace omnilight\scheduling;

use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\di\Instance;


/**
 * Run the scheduled commands
 */
class ScheduleController extends Controller
{
    /**
     * @var string|Schedule
     */
    public string|Schedule $schedule = 'schedule';

    /**
     * @var string|null Schedule file that will be used to run schedule
     */
    public string|null $scheduleFile;

    /**
     * @var bool set to true to avoid error output
     */
    public bool $omitErrors = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID),
            $actionID == 'run' ? ['scheduleFile', 'omitErrors'] : []
        );
    }

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        if (Yii::$app->has($this->schedule)) {
            $this->schedule = Instance::ensure($this->schedule, Schedule::className());
        } else {
            $this->schedule = Yii::createObject(Schedule::className());
        }
        parent::init();
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionRun(): void
    {
        $this->importScheduleFile();

        $events = $this->schedule->dueEvents(Yii::$app);

        foreach ($events as $event) {
            $event->omitErrors($this->omitErrors);
            $this->stdout('Running scheduled command: '.$event->getSummaryForDisplay()."\n");
            $event->run(Yii::$app);
        }

        if (count($events) === 0)
        {
            $this->stdout("No scheduled commands are ready to run.\n");
        }
    }

    protected function importScheduleFile(): void
    {
        if ($this->scheduleFile === null) {
            return;
        }

        $scheduleFile = Yii::getAlias($this->scheduleFile);
        if (!file_exists($scheduleFile)) {
            $this->stderr('Can not load schedule file '.$this->scheduleFile."\n");
            return;
        }

        $schedule = $this->schedule;
        call_user_func(function() use ($schedule, $scheduleFile) {
            include $scheduleFile;
        });
    }
}