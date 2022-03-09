<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Product;
use App\Models\User;
use App\Models\Client;
use App\Models\Script;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use PDO;
use Illuminate\Validation\ValidationException;

class TeleoperateurController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function callSetup(Request $request)
    {
        if (Auth::user()->type === 'teleoperateur') {
            $clients = Client::where('teamid', '=', Auth::user()->teamid)->orderBy('name')->get(['id', 'name', 'email', 'phone', 'position', 'company']);
            // dd($clients);
            $scripts = Script::where('teamid', '=', Auth::user()->teamid)->orderBy('name')->get(['id', 'name', 'teamid']);
            // dd($scripts);
            // $products = Product::where('teamid', '=', Auth::user()->teamid)->get(['id', 'name', 'price', 'quantity', 'teamid']);

            return response()->view('Views-teleoperateur/teleoperateur-dashboard', compact('clients', 'scripts'));
        } else return redirect('404');
    }

    public function callStart(Request $request)
    {
        if (Auth::user()->type === 'teleoperateur') {
            // dd($request);
            $attributes = $request->validate([
                'client' => ['required', 'string', 'max:200'],
                'script' => ['required', 'string', 'max:200'],
            ]);
            $client = Client::where('name', '=', $request->client)->get(['id', 'name', 'gender', 'email', 'phone', 'position', 'company', 'country', 'city', 'address', 'zip', 'teamid'])->first();
            $script = Script::where('name', '=', $request->script)->get(['id', 'name', 'content', 'teamid'])->first();
            $products = Product::where('teamid', '=', Auth::user()->teamid)->orderBy('name')->get(['id', 'name', 'price', 'quantity', 'teamid']);
            // dd($products);
            if ($client->teamid === Auth::user()->teamid && $script->teamid === Auth::user()->teamid) {
                // return response()->view('Views-teleoperateur/teleoperateur-equipe', compact('client', 'script'));
                // dd($script);
                return response()->view('Views-teleoperateur/appel', compact('client', 'script', 'products'));
            } else return redirect('404');
        } else return redirect('404');
    }

    public function callSave(Request $request)
    {
        // dd(Product::where('name', '=', $request->prodSelection)->first());
        if (Auth::user()->type === 'teleoperateur') {
            // dd(Client::where('id', '=', $request->callClient)->first()->name);
            $attributes = $request->validate([
                'callLength' => ['required', 'string', 'max:200'],
                'callScript' => ['required', 'string', 'max:200'],
                'callClient' => ['required', 'string', 'max:200'],
                'callResult' => ['required', 'string', 'max:200'],
            ]);
            $call = new Call;
            $call->teleoperateur = Auth::user()->name;
            $call->client = Client::where('id', '=', $request->callClient)->first()->name;
            $call->script = Script::where('id', '=', $request->callScript)->first()->name;
            $call->callDate = date('d/m/Y');
            $call->callLength = $request->callLength;
            $call->teleoperateurId = Auth::user()->id;
            $call->clientId = $request->callClient;
            $call->result = $request->callResult;
            $call->teamid = Auth::user()->teamid;

            if ($request->prodQuantity > 0 && $request->callResult === "Vente réussie") {
                $call->quantity = $request->prodQuantity;
                $call->product = $request->prodSelection;
                $product = Product::where('name', '=', $request->prodSelection)->first();
                $product->quantity = $product->quantity - $request->prodQuantity;
                $product->save();
                $call->productId = $product->id;
            } else {
                $call->quantity = null;
                $call->product = null;
                $call->productId = null;
            }
            // dd($call);
            $call->save();
            return redirect('dashboard');
        } else return redirect('404');
    }
}
