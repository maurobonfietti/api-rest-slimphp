<?php

declare(strict_types=1);

namespace App\Service\Note;

use App\Exception\Note;

final class Create extends Base
{
    public function create(array $input): object
    {
        $data = json_decode((string) json_encode($input), false);
        if (! isset($data->name)) {
            throw new Note('Invalid data: name is required.', 400);
        }
//        self::validateNoteName($data->name);
//        $data->description = $data->description ?? null;
//        $note = $this->noteRepository->createNote($data);
        $mynote = new \App\Entity\Note();
        $mynote->updateName(self::validateNoteName($data->name));
        $desc = isset($data->description) ? $data->description : null;
        $mynote->updateDescription($desc);
//        $mynote->setDescription($data->description ?? null);
        /** @var \App\Entity\Note $note */
        $note = $this->noteRepository->createNote($mynote)->getData2();
        var_dump($note, $note->getId()); exit;
//        var_dump($note->getId()); exit;
//        var_dump($note['id']); exit;
        if (self::isRedisEnabled() === true) {
//            $this->saveInCache($note['id'], $note);
            $this->saveInCache($note->getId(), $note);
        }

        return $note;
    }
}
