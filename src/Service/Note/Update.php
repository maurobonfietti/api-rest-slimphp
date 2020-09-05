<?php

declare(strict_types=1);

namespace App\Service\Note;

final class Update extends Base
{
    public function update(array $input, int $noteId): array
    {
        $note = $this->getOneFromDb($noteId);
        $data = json_decode((string) json_encode($input), false);
        if (isset($data->name)) {
//            $note->name = self::validateNoteName($data->name);
            $note->setName(self::validateNoteName($data->name));
        }
        if (isset($data->description)) {
            $note->setDescription($data->description);
        }
        $notes = $this->noteRepository->updateNote($note)->getData3();
        if (self::isRedisEnabled() === true) {
            $this->saveInCache($notes->id, $notes);
        }

        return $notes;
    }
}
