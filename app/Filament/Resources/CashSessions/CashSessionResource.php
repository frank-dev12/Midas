<?php

namespace App\Filament\Resources\CashSessions;

use App\Filament\Resources\CashSessions\Pages\ManageCashSessions;
use App\Models\CashSession;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Stub de sesiones de caja. Apertura/cierre completo pendiente.
 */
class CashSessionResource extends Resource
{
    protected static ?string $model = CashSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Caja';

    protected static ?string $modelLabel = 'sesión de caja';

    protected static ?string $pluralModelLabel = 'sesiones de caja';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('status')->label('Estado'),
                TextEntry::make('opening_amount')->label('Apertura')->money('PEN'),
                TextEntry::make('closing_amount')->label('Cierre')->money('PEN'),
                TextEntry::make('opened_at')->label('Abierta')->dateTime('d/m/Y H:i'),
                TextEntry::make('closed_at')->label('Cerrada')->dateTime('d/m/Y H:i'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('openedBy.name')->label('Abierta por'),
                TextColumn::make('opening_amount')->label('Apertura')->money('PEN'),
                TextColumn::make('closing_amount')->label('Cierre')->money('PEN'),
                TextColumn::make('opened_at')->label('Desde')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCashSessions::route('/'),
        ];
    }
}
