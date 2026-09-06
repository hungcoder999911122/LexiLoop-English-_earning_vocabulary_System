package com.example.englishvocab.controller;

import com.example.englishvocab.dto.ApiDtos.VocabDto;
import com.example.englishvocab.dto.ApiDtos.VocabUpsert;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.repository.PartOfSpeechRepository;
import com.example.englishvocab.service.VocabularyService;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/vocabularies")
public class VocabularyController {
    private final VocabularyService vocabularyService;
    private final PartOfSpeechRepository posRepository;

    public VocabularyController(VocabularyService vocabularyService, PartOfSpeechRepository posRepository) {
        this.vocabularyService = vocabularyService;
        this.posRepository = posRepository;
    }

    @GetMapping("/topic/{topicId}")
    public List<VocabDto> byTopic(@PathVariable Integer topicId, @AuthenticationPrincipal User user) {
        return vocabularyService.byTopic(topicId, user == null ? null : user.getUserID());
    }

    @GetMapping("/{id}")
    public VocabDto get(@PathVariable Integer id, @AuthenticationPrincipal User user) {
        return vocabularyService.get(id, user == null ? null : user.getUserID());
    }

    @GetMapping("/search")
    public List<VocabDto> search(@RequestParam String q, @AuthenticationPrincipal User user) {
        return vocabularyService.search(q, user == null ? null : user.getUserID());
    }

    @GetMapping("/pos")
    public Object pos() {
        return posRepository.findAll();
    }

    @PostMapping
    @PreAuthorize("hasRole('ADMIN')")
    public VocabDto create(@RequestBody VocabUpsert req) {
        return vocabularyService.create(req);
    }

    @PutMapping("/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public VocabDto update(@PathVariable Integer id, @RequestBody VocabUpsert req) {
        return vocabularyService.update(id, req);
    }

    @DeleteMapping("/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public void delete(@PathVariable Integer id) {
        vocabularyService.delete(id);
    }
}
