<?php

declare(strict_types=1);

use Awcodes\Mason\Support\BrickCommand;

describe('BrickCommand', function () {
    it('creates a command with make()', function () {
        $command = BrickCommand::make('testCommand', ['foo' => 'bar']);

        expect($command->name)->toBe('testCommand')
            ->and($command->arguments)->toBe(['foo' => 'bar']);
    });

    it('creates insertBrick command', function () {
        $brick = ['type' => 'masonBrick', 'attrs' => ['id' => 'test']];
        $command = BrickCommand::insertBrick($brick, 2);

        expect($command->name)->toBe('insertBrick')
            ->and($command->arguments)->toBe([
                'brick' => $brick,
                'position' => 2,
            ]);
    });

    it('creates updateBrick command', function () {
        $brick = ['type' => 'masonBrick', 'attrs' => ['id' => 'test', 'config' => ['title' => 'Updated']]];
        $command = BrickCommand::updateBrick(1, $brick);

        expect($command->name)->toBe('updateBrick')
            ->and($command->arguments)->toBe([
                'index' => 1,
                'brick' => $brick,
            ]);
    });

    it('creates deleteBrick command', function () {
        $command = BrickCommand::deleteBrick(3);

        expect($command->name)->toBe('deleteBrick')
            ->and($command->arguments)->toBe([
                'index' => 3,
            ]);
    });

    it('creates moveBrick command', function () {
        $command = BrickCommand::moveBrick(0, 2);

        expect($command->name)->toBe('moveBrick')
            ->and($command->arguments)->toBe([
                'from' => 0,
                'to' => 2,
            ]);
    });

    it('serializes to array', function () {
        $command = BrickCommand::make('testCommand', ['key' => 'value']);

        expect($command->toArray())->toBe([
            'name' => 'testCommand',
            'arguments' => ['key' => 'value'],
        ]);
    });

    it('serializes insertBrick to array', function () {
        $brick = ['type' => 'masonBrick'];
        $command = BrickCommand::insertBrick($brick, 0);

        expect($command->toArray())->toBe([
            'name' => 'insertBrick',
            'arguments' => [
                'brick' => $brick,
                'position' => 0,
            ],
        ]);
    });
});
