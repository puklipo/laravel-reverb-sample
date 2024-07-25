# Laravel Reverbのサンプルプロジェクト

ローカルで動かすまで。

## バージョン
- Laravel 11.x
- Laravel Reverb 1.0
- Breeze 2.x Livewire (Volt Functional API) with Alpine
- Livewire 3.x Volt 1.x
- Laravel Installer 5.8

Laravelはバージョンが変わったら古い情報が役に立たなくなるので必ずバージョンを確認する。

## 必要な前提知識
Reverbを使うにはLaravelのイベント機能、ブロードキャスト機能の理解が必須。

警告として先に書いておくと、ローカルで動かすだけなら適当にコピペすればいいだけで簡単だけど本番サーバーで稼働させようとすると難易度が跳ね上がるので自力でサーバー構築できない人はReverb使うのはやめたほうがいい。

## プロジェクト作成

```bash
laravel new laravel-reverb-sample
```
Breeze、Livewire(Volt Functional)、PHPUnit、SQLiteなどを選択。

```bash
cd laravel-reverb-sample
php artisan install:broadcasting
```
Reverbインストールを選択。

ターミナルの別タブなどでLaravelとReverbを同時に起動したままにする。
```bash
php artisan serve
```
```bash
php artisan reverb:start
```

準備はここまでだけどReverbはインストールしただけで使えるスターターキットではないのでこのままでは何も使えない。

Reverbが提供するのはWebSocketサーバー機能。
サーバーサイド：LaravelからメッセージなどをWebSocketサーバーに送信。
クライアントサイド：Laravel Echoなどを使ってWebSocketサーバーから受信。
Reverbは中間だけなので前後の部分は自分で作らなければならない。
何を送信して、受信したものをどう表示するかはプロジェクトごとに違うので当たり前の話。

## サーバーサイドから送信
「別にどんな送信方法でもいい」ことを示すために今回はartisanコマンドからの送信。

イベントとコマンドを作成。

```bash
php artisan make:event MessageCreated
```

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $created_at;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user, public string $message)
    {
        $this->created_at = now()->toDateTimeString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('messages.'.$this->user->id),
        ];
    }
}
```

キューを使わずすぐ送信するので`ShouldBroadcastNow`を指定。  
キューを使うなら`ShouldBroadcast`にして`php artisan queue:listen`（開発時用）でキューワーカーを起動。

```bash
php artisan make:command ReverbTest
```
```php
<?php

namespace App\Console\Commands;

use App\Events\MessageCreated;
use App\Models\User;
use Illuminate\Console\Command;

class ReverbTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        MessageCreated::dispatch(User::findOrFail(1), 'test');

        //もしくは
        //MessageCreated::broadcast(User::findOrFail(1), 'test');

        return 0;
    }
}
```

`php artisan reverb:test`で送信できるので最初にさっとテストしたい段階ではこれで十分。

## クライアントサイドで受信
今回はLivewire Voltでの例。
https://livewire.laravel.com/docs/events#real-time-events-using-laravel-echo

```bash
php artisan make:volt message
```

Voltの場合は`on()`を使う。普通のLivewireの場合は`Livewire\Attributes\On`

```php
<?php

use function Livewire\Volt\{state, on};

state(['user', 'messages' => collect()]);

on(['echo-private:messages.{user},MessageCreated' => function (array $event) {
    $this->messages->prepend($event);
}])
?>

<div>
    @forelse($messages as $message)
        <div>{{ $message['message'] }} <span class="pl-6 text-gray-400 text-sm">{{ $message['created_at'] }}</span></div>
    @empty
        <div>empty</div>
    @endforelse
</div>
```

`resources/views/dashboard.blade.php`にLivewireコンポーネントを追加。
```php
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <livewire:message :user="auth()->id()" />
                </div>
            </div>
        </div>
    </div>
```

VueやReactの場合はEchoを使う。
```js
Echo.private(`messages.${this.user.id}`)
    .listen('MessageCreated', (e) => {
        console.log(e.message);
    });
```

bladeやjsを変更したらアセットを再ビルド。
```bash
npm run build
```

## routes/channels.php の設定
今回はプライベートチャンネルなので認可が必要。

```php
Broadcast::channel('messages.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
```

## 動作確認
ターミナルのタブを3つ開き、LaravelとReverbを起動。
```bash
php artisan serve
```
```bash
php artisan reverb:start
```
ブラウザで `http://127.0.0.1:8000/` を開き新規ユーザー登録、ダッシュボードページを表示。

3つ目のタブで、テストメッセージ送信。
```bash
php artisan reverb:test
```
何度送信する度に自動で更新されれば成功。

ここに書いたこと以外のことは何もする必要がないので失敗する理由がない。
質問サイトでよく見る失敗理由は「どこにも書かれてない、しなくていい余計なことをしてるから」なことがものすごく多い。
`.env`やconfigファイルを意味なく変更するとか`config:cache`するとかそういう余計なことは何もしなくていい。

## 動かないときは
ブラウザの開発ツールを見る。

コンソールで`http://127.0.0.1:8000/broadcasting/auth 403 (Forbidden)`のエラーが出てる場合、`routes/channels.php`の設定ができてない。

メッセージを送信しても更新されない場合、ネットワークの`Fetch/XHR`や`WS`を見る。

## 実践
artisanコマンドで送信する以外の使い方、と言っても送信したい場所でイベントのdispatch()を呼び出すだけなのでどこでも使える。

```php
MessageCreated::dispatch()
```

Laravelのイベント機能が一番最初。イベントをWebSocketで送信するのがブロードキャスト機能。Reverb登場前はPusherやサードパーティのWebSocketサーバーを使うしかなかった。ReverbはLaravel公式のWebSocketサーバー。

## 運用
Laravel公式なのでForgeでもしっかりサポートされているので本番サーバーでの運用はForge使うのが一番簡単。
Forgeを使わないならドキュメントを読んで自力でどうにかするしかない。

Reverb発表時はVaporにも対応するみたいなこと言ってたけど現時点では対応してない。
