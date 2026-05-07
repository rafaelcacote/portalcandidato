<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Modules\Shared\Enums\SelectionProcessStatus;

class SelectionProcessService
{
    public function publish(SelectionProcess $selectionProcess): SelectionProcess
    {
        $selectionProcess->update([
            'status' => SelectionProcessStatus::Ativo->value,
        ]);

        return $selectionProcess->refresh();
    }

    public function close(SelectionProcess $selectionProcess): SelectionProcess
    {
        $selectionProcess->update([
            'status' => SelectionProcessStatus::Encerrado->value,
        ]);

        return $selectionProcess->refresh();
    }
}
