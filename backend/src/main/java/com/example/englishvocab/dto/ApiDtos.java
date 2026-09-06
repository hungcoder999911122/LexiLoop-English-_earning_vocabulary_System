package com.example.englishvocab.dto;

import java.time.LocalDate;
import java.util.List;
import java.util.Map;

public final class ApiDtos {
    private ApiDtos() {}

    public record RegisterRequest(String userName, String email, String password, String fullName) {}
    public record LoginRequest(String userName, String password) {}
    public record AuthResponse(String token, Integer userId, String userName, String fullName, String role) {}

    public record TopicDto(Integer topicId, String topicName, String description, String cefrLevel,
                           Boolean isPublic, long vocabCount) {}
    public record TopicUpsert(String topicName, String description, String cefrLevel, Boolean isPublic) {}

    public record ExampleDto(Integer exampleId, String sentenceEn, String sentenceVi) {}
    public record SenseDto(Integer senseId, String posCode, String posName, String meaningVi, String meaningEn,
                           List<ExampleDto> examples) {}
    public record VocabDto(Integer vocabularyId, Integer topicId, String topicName, String word, String pronunciation,
                           String audioUrl, String difficulty, boolean favorite, List<SenseDto> senses) {}
    public record VocabUpsert(Integer topicId, String word, String pronunciation, String audioUrl, String difficulty,
                              Integer partOfSpeechId, String meaningVi, String meaningEn,
                              String exampleEn, String exampleVi) {}

    public record ReviewRequest(Integer vocabularyId, Integer quality, Integer responseTimeMs) {}
    public record ReviewResponse(LocalDate nextReviewDate, String mastery, Integer intervalDays) {}

    public record QuizStartRequest(Integer topicId, Integer numQuestions) {}
    public record QuizAnswerOption(Integer answerId, String answerText) {}
    public record QuizQuestionDto(Integer questionId, String questionText, String type, List<QuizAnswerOption> answers) {}
    public record QuizDto(Integer quizId, String title, List<QuizQuestionDto> questions) {}
    public record QuizSubmitItem(Integer questionId, Integer answerId) {}
    public record QuizSubmitRequest(Integer quizId, Integer durationSec, List<QuizSubmitItem> answers) {}
    public record QuizResultDto(Integer resultId, Integer score, Integer total, double percent, Integer durationSec) {}

    public record SessionStart(Integer topicId, String studyMode) {}
    public record SessionEnd(Integer itemsStudied, Integer correctCount, Integer wrongCount) {}

    public record StatsDto(Map<String, Object> stats, List<Map<String, Object>> due, List<Map<String, Object>> recentQuizzes) {}
}
