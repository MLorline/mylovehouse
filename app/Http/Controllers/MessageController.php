<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function getConversations(Request $request)
    {
        $user = $request->user();
        $conversations = $user->conversations()->with(['lastMessage', 'participants'])->get();
        
        return response()->json($conversations);
    }
    
    public function getMessages(Request $request, Conversation $conversation)
    {
        // Vérifier que l'utilisateur est un participant
        if (!$conversation->participants->contains($request->user()->id)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $messages = $conversation->messages()->with('user')->orderBy('created_at', 'asc')->get();
        
        // Marquer les messages comme lus
        $conversation->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->update(['is_read' => true]);
            
        return response()->json($messages);
    }
    
    public function sendMessage(Request $request, Conversation $conversation)
    {
        // Vérifier que l'utilisateur est un participant
        if (!$conversation->participants->contains($request->user()->id)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $validatedData = $request->validate([
            'content' => 'required|string',
        ]);
        
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'content' => $validatedData['content'],
        ]);
        
        // Si vous avez un système de notifications en temps réel (WebSockets)
        // broadcastMessage($message);
        
        return response()->json($message, 201);
    }
    
    public function startConversation(Request $request)
    {
        $validatedData = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'house_id' => 'nullable|exists:houses,id',
            'content' => 'required|string',
        ]);
        
        $user = $request->user();
        $recipient = User::find($validatedData['recipient_id']);
        
        // Vérifier si une conversation existe déjà
        $existingConversation = Conversation::whereHas('participants', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereHas('participants', function($query) use ($recipient) {
            $query->where('user_id', $recipient->id);
        });
        
        if (isset($validatedData['house_id'])) {
            $existingConversation->where('house_id', $validatedData['house_id']);
        }
        
        $conversation = $existingConversation->first();
        
        // Si aucune conversation n'existe, en créer une nouvelle
        if (!$conversation) {
            $conversation = Conversation::create([
                'house_id' => $validatedData['house_id'] ?? null,
            ]);
            
            // Ajouter les participants
            $conversation->participants()->attach([$user->id, $recipient->id]);
        }
        
        // Créer le premier message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $validatedData['content'],
        ]);
        
        return response()->json([
            'conversation' => $conversation->load('participants'),
            'message' => $message,
        ], 201);
    }
}
