<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\Pages\ManageSales;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Stub de ventas: lectura básica. Creación vía POS (pendiente persistencia completa).
 */
class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $modelLabel = 'venta';

    protected static ?string $pluralModelLabel = 'ventas';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number')->label('Número'),
                TextEntry::make('status')->label('Estado'),
                TextEntry::make('total')->label('Total')->money('PEN'),
                TextEntry::make('payment_method')->label('Pago'),
                TextEntry::make('sunat_status')->label('SUNAT'),
                TextEntry::make('sold_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Número')->placeholder('—'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('total')->label('Total')->money('PEN'),
                TextColumn::make('payment_method')->label('Pago'),
                TextColumn::make('sunat_status')->label('SUNAT')->placeholder('—'),
                TextColumn::make('sold_at')->label('Fecha')->dateTime('d/m/Y H:i'),
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
            'index' => ManageSales::route('/'),
        ];
    }
}
